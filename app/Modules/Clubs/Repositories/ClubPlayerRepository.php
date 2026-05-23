<?php

namespace App\Modules\Clubs\Repositories;

use App\Modules\Clubs\Filters\ClubPlayersFilter;
use App\Modules\Clubs\Interfaces\ClubPlayerInterface;
use App\Modules\Clubs\Models\ClubPlayer;
use App\Modules\Players\Models\Player;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ClubPlayerRepository extends BaseRepository implements ClubPlayerInterface
{
    public function __construct(ClubPlayer $model, private Player $player)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): ClubPlayersFilter
    {
        return new ClubPlayersFilter($builder);
    }

    public function getClubPlayers(string $clubId, array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with([
                'player' => fn ($q) => $q->select('id', 'name', 'profile_photo_url'),
                'beltRank',
            ])
            ->where('club_id', $clubId);

        (new ClubPlayersFilter($query))->attachFilterQuery($filters);

        $allowedSorts = ['started_at', 'ended_at'];
        $sortBy = in_array($filters['sort_by'] ?? null, $allowedSorts, true) ? $filters['sort_by'] : 'started_at';
        $sortOrder = in_array($filters['sort_order'] ?? null, ['asc', 'desc'], true) ? $filters['sort_order'] : 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($filters['per_page'] ?? 15);
    }

    public function getAllClubPlayers(string $clubId): Collection
    {
        return $this->model->newQuery()
            ->with([
                'player' => fn ($q) => $q->select('id', 'name', 'profile_photo_url'),
                'beltRank',
            ])
            ->where('club_id', $clubId)
            ->orderByRaw('ended_at IS NOT NULL')
            ->orderBy('started_at', 'desc')
            ->get();
    }

    public function getAvailablePlayers(string $clubId): Collection
    {
        $activeMemberIds = $this->model->newQuery()
            ->whereNull('ended_at')
            ->pluck('player_id');

        return $this->player->newQuery()
            ->select('id', 'name', 'profile_photo_url')
            ->whereNotIn('id', $activeMemberIds)
            ->orderBy('name')
            ->get();
    }

    public function assignPlayer(string $clubId, array $validated): ClubPlayer
    {
        $alreadyActive = $this->model->newQuery()
            ->where('player_id', $validated['player_id'])
            ->whereNull('ended_at')
            ->exists();

        if ($alreadyActive) {
            throw new \RuntimeException('This player already has an active membership at another club.');
        }

        return $this->model->newQuery()->create([
            'club_id' => $clubId,
            'player_id' => $validated['player_id'],
            'started_at' => $validated['started_at'],
            'belt_rank_id' => $validated['belt_rank_id'] ?? null,
            'grade_certificate' => $validated['grade_certificate'] ?? null,
        ]);
    }

    public function endMembership(string $clubId, string $membershipId, array $validated): ClubPlayer
    {
        $membership = $this->model->newQuery()
            ->where('club_id', $clubId)
            ->findOrFail($membershipId);

        $membership->update(['ended_at' => $validated['ended_at']]);

        return $membership;
    }

    public function updateMembershipDetails(string $clubId, string $membershipId, array $validated): ClubPlayer
    {
        $membership = $this->model->newQuery()
            ->where('club_id', $clubId)
            ->findOrFail($membershipId);

        $membership->update([
            'belt_rank_id' => $validated['belt_rank_id'] ?? null,
            'grade_certificate' => $validated['grade_certificate'] ?? null,
        ]);

        return $membership;
    }
}
