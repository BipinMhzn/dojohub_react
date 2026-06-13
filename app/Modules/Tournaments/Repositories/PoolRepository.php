<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\PoolInterface;
use App\Modules\Tournaments\Models\TournamentBracket;
use App\Modules\Tournaments\Models\TournamentPool;
use App\Modules\Tournaments\Models\TournamentPoolCompetitor;
use App\Modules\Tournaments\Models\TournamentRegistration;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\TournamentCategory;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Class PoolRepository
 */
final class PoolRepository extends BaseRepository implements PoolInterface
{
    public function __construct(
        private readonly TournamentBracket $bracket,
        private readonly TournamentRegistration $registration,
        private readonly TournamentPoolCompetitor $poolCompetitor,
        TournamentPool $model,
    ) {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): void {}

    public function getPoolsForCategory(TournamentCategory $category): Collection
    {
        $bracket = $this->bracket->newQuery()->where('tournament_category_id', $category->id)->first();

        if (! $bracket) {
            return collect();
        }

        return $this->model->newQuery()
            ->where('bracket_id', $bracket->id)
            ->with(['competitors.registration.player', 'competitors.registration.club'])
            ->orderBy('name')
            ->get();
    }

    public function getConfirmedRegistrations(TournamentCategory $category): Collection
    {
        return $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->where('status', RegistrationStatusEnum::Confirmed)
            ->with('player:id,name')
            ->get(['id', 'player_id', 'tournament_category_id', 'status']);
    }

    /**
     * @throws TournamentCancelledException
     * @throws ValidationException
     */
    public function assignCompetitor(TournamentCategory $category, string $poolId, string $registrationId): TournamentPoolCompetitor
    {
        $this->assertTournamentNotCancelled($category);

        $pool = $this->findCategoryPool($category, $poolId);

        $registration = $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->where('status', RegistrationStatusEnum::Confirmed)
            ->findOrFail($registrationId);

        // A competitor belongs to exactly one pool: clear any existing membership in this bracket first.
        $bracketPoolIds = $this->model->newQuery()->where('bracket_id', $pool->bracket_id)->pluck('id');
        $this->poolCompetitor->newQuery()
            ->whereIn('pool_id', $bracketPoolIds)
            ->where('registration_id', $registration->id)
            ->delete();

        $existing = $this->poolCompetitor->newQuery()
            ->withTrashed()
            ->where('pool_id', $pool->id)
            ->where('registration_id', $registration->id)
            ->first();

        if ($existing) {
            $existing->restore();

            return $existing;
        }

        return $this->poolCompetitor->newQuery()->create([
            'pool_id' => $pool->id,
            'registration_id' => $registration->id,
        ]);
    }

    /**
     * @throws TournamentCancelledException
     */
    public function removeCompetitor(TournamentCategory $category, string $poolCompetitorId): true
    {
        $this->assertTournamentNotCancelled($category);

        $bracketPoolIds = $this->bracketPoolIds($category);

        $this->poolCompetitor->newQuery()
            ->whereIn('pool_id', $bracketPoolIds)
            ->where('id', $poolCompetitorId)
            ->delete();

        return true;
    }

    private function findCategoryPool(TournamentCategory $category, string $poolId): TournamentPool
    {
        return $this->model->newQuery()
            ->whereIn('bracket_id', $this->bracket->newQuery()->where('tournament_category_id', $category->id)->pluck('id'))
            ->findOrFail($poolId);
    }

    private function bracketPoolIds(TournamentCategory $category): Collection
    {
        $bracketIds = $this->bracket->newQuery()->where('tournament_category_id', $category->id)->pluck('id');

        return $this->model->newQuery()->whereIn('bracket_id', $bracketIds)->pluck('id');
    }

    /**
     * @throws TournamentCancelledException
     */
    private function assertTournamentNotCancelled(TournamentCategory $category): void
    {
        if ($category->tournament->status === TournamentStatusEnum::Cancelled) {
            throw new TournamentCancelledException;
        }
    }
}
