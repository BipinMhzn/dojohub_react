<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\Tournaments\Exceptions\PlayerAlreadyRegisteredException;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Models\Club;
use App\Modules\Tournaments\Models\Player;
use App\Modules\Tournaments\Models\Tournament;
use App\Modules\Tournaments\Models\TournamentRegistration;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class TournamentRepository
 */
final class RegistrationRepository extends BaseRepository implements RegistrationInterface
{
    /**
     * TournamentRepository constructor.
     */
    public function __construct(
        private readonly Tournament $tournament,
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

    public function getTournamentRegistrations(Tournament $tournament): Collection
    {
        return $this->registration->newQuery()
            ->where('tournament_id', $tournament->id)
            ->with(['player', 'club'])
            ->get();
    }

    public function getClubList(): Collection
    {
        return $this->club->newQuery()->orderBy('name')->get();
    }

    public function getPlayerList(Tournament $tournament): Collection
    {
        $maxDob = Carbon::now()->subYears($tournament->min_age)->endOfDay();
        $minDob = Carbon::now()->subYears($tournament->max_age)->startOfDay();

        return $this->player->newQuery()
            ->whereBetween('dob', [$minDob, $maxDob])
            ->whereBetween('weight', [$tournament->min_weight, $tournament->max_weight])
            ->where('gender', $tournament->gender)
            ->orderBy('name')
            ->get();
    }

    /**
     * @throws PlayerAlreadyRegisteredException
     */
    public function getRegisteringPlayerById(Tournament $tournament, string $playerId, ?string $registrationId = null): Player
    {
        $maxDob = Carbon::now()->subYears($tournament->min_age)->endOfDay();
        $minDob = Carbon::now()->subYears($tournament->max_age)->startOfDay();

        $player = $this->player->newQuery()
            ->whereBetween('dob', [$minDob, $maxDob])
            ->whereBetween('weight', [$tournament->min_weight, $tournament->max_weight])
            ->where('gender', $tournament->gender)
            ->where('id', $playerId)
            ->firstOrFail();

        $registeredPlayer = $this->registration->newQuery()
            ->where('tournament_id', $tournament->id)
            ->where('player_id', $playerId)
            ->whereNot('id', $registrationId)
            ->first();

        if ($registeredPlayer) {
            throw new PlayerAlreadyRegisteredException;
        }

        return $player;
    }

    public function getScheduledTournamentById(string $tournamentId): Tournament
    {
        return $this->tournament->newQuery()
            ->where('status', TournamentStatusEnum::SCHEDULED)
            ->findOrFail($tournamentId);
    }

    public function updateTournamentRegistration(Tournament $tournament, string $registrationId, array $inputs): Tournament
    {
        $registration = $this->registration->newQuery()
            ->where('tournament_id', $tournament->id)
            ->findOrFail($registrationId);

        $registration->fill($inputs);
        $registration->save();

        return $registration;
    }

    public function destroyTournamentRegistration(Tournament $tournament, string $registrationId): true
    {
        $registration = $this->registration->newQuery()
            ->where('tournament_id', $tournament->id)
            ->findOrFail($registrationId);

        $registration->delete();

        return true;
    }
}
