<?php

namespace App\Modules\Clubs\Repositories;

use App\Modules\Clubs\Filters\ClubCoachesFilter;
use App\Modules\Clubs\Interfaces\ClubCoachInterface;
use App\Modules\Clubs\Models\ClubCoach;
use App\Modules\Coaches\Models\Coach;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ClubCoachRepository extends BaseRepository implements ClubCoachInterface
{
    public function __construct(ClubCoach $model, private Coach $coach)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): ClubCoachesFilter
    {
        return new ClubCoachesFilter($builder);
    }

    public function getClubCoaches(string $clubId, array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['coach' => fn ($q) => $q->select('id', 'full_name')])
            ->where('club_id', $clubId);

        (new ClubCoachesFilter($query))->attachFilterQuery($filters);

        $allowedSorts = ['started_at', 'ended_at'];
        $sortBy = in_array($filters['sort_by'] ?? null, $allowedSorts, true) ? $filters['sort_by'] : 'started_at';
        $sortOrder = in_array($filters['sort_order'] ?? null, ['asc', 'desc'], true) ? $filters['sort_order'] : 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($filters['per_page'] ?? 15);
    }

    public function getAvailableCoaches(string $clubId): Collection
    {
        $activeMemberIds = $this->model->newQuery()
            ->where('club_id', $clubId)
            ->whereNull('ended_at')
            ->pluck('coach_id');

        return $this->coach->newQuery()
            ->select('id', 'full_name')
            ->whereNotIn('id', $activeMemberIds)
            ->orderBy('full_name')
            ->get();
    }

    public function assignCoach(string $clubId, array $validated): ClubCoach
    {
        $alreadyActive = $this->model->newQuery()
            ->where('club_id', $clubId)
            ->where('coach_id', $validated['coach_id'])
            ->whereNull('ended_at')
            ->exists();

        if ($alreadyActive) {
            throw new \RuntimeException('This coach already has an active membership at this club.');
        }

        return $this->model->newQuery()->create([
            'club_id' => $clubId,
            'coach_id' => $validated['coach_id'],
            'started_at' => $validated['started_at'],
        ]);
    }

    public function endMembership(string $clubId, string $membershipId, array $validated): ClubCoach
    {
        $membership = $this->model->newQuery()
            ->where('club_id', $clubId)
            ->findOrFail($membershipId);

        $membership->update(['ended_at' => $validated['ended_at']]);

        return $membership;
    }
}
