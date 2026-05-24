<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\TournamentCategories\Models\TournamentCategory;
use App\Modules\Tournaments\Exceptions\PlayerAlreadyRegisteredException;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Models\Club;
use App\Modules\Tournaments\Models\Player;
use App\Modules\Tournaments\Models\Tournament;
use App\Modules\Tournaments\Models\TournamentRegistration;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class RegistrationRepository
 */
final class RegistrationRepository extends BaseRepository implements RegistrationInterface
{
    public function __construct(
        private readonly Tournament $tournament,
        private readonly TournamentCategory $category,
        private readonly TournamentRegistration $registration,
        private readonly Player $player,
        private readonly Club $club
    ) {
        parent::__construct($registration);
    }

    public function getFilter(Builder $builder): void {}

    public function getTournamentById(string $tournamentId): Tournament
    {
        return $this->tournament->newQuery()
            ->with(['venue' => function ($query) {
                $query->select(['id', 'name']);
            }])
            ->findOrFail($tournamentId);
    }

    public function getCategoryById(string $categoryId): TournamentCategory
    {
        return $this->category->newQuery()
            ->with(['tournament.venue'])
            ->findOrFail($categoryId);
    }

    public function getCategoryRegistrations(TournamentCategory $category): Collection
    {
        return $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->with(['player', 'club'])
            ->get();
    }

    public function getClubList(): Collection
    {
        return $this->club->newQuery()->orderBy('name')->get();
    }

    public function getPlayerList(TournamentCategory $category): Collection
    {
        $maxDob = Carbon::now()->subYears($category->min_age)->endOfDay();
        $minDob = Carbon::now()->subYears($category->max_age)->startOfDay();

        $registeredPlayerIds = $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->pluck('player_id');

        return $this->player->newQuery()
            ->whereBetween('dob', [$minDob, $maxDob])
            ->whereBetween('weight', [$category->min_weight, $category->max_weight])
            ->where('gender', $category->gender)
            ->whereNotIn('id', $registeredPlayerIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * @throws PlayerAlreadyRegisteredException
     */
    public function getRegisteringPlayerById(TournamentCategory $category, string $playerId, ?string $registrationId = null): Player
    {
        $maxDob = Carbon::now()->subYears($category->min_age)->endOfDay();
        $minDob = Carbon::now()->subYears($category->max_age)->startOfDay();

        $player = $this->player->newQuery()
            ->whereBetween('dob', [$minDob, $maxDob])
            ->whereBetween('weight', [$category->min_weight, $category->max_weight])
            ->where('gender', $category->gender)
            ->where('id', $playerId)
            ->firstOrFail();

        $existing = $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->where('player_id', $playerId)
            ->whereNot('id', $registrationId)
            ->first();

        if ($existing) {
            throw new PlayerAlreadyRegisteredException;
        }

        return $player;
    }

    public function destroyTournamentRegistration(TournamentCategory $category, string $registrationId): true
    {
        $registration = $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->findOrFail($registrationId);

        $registration->delete();

        return true;
    }
}
