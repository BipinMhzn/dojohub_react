<?php

namespace App\Modules\Clubs\Repositories;

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

    public function getFilter(Builder $builder): never
    {
        throw new \LogicException('ClubPlayerRepository does not use getFilter.');
    }

    public function getClubPlayers(string $clubId, array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['player' => function ($query) {
                $query->select('id', 'name', 'profile_photo_url');
            }])
            ->where('club_id', $clubId);

        if (($filters['status'] ?? 'all') === 'active') {
            $query->whereNull('ended_at');
        }

        return $query->orderBy('started_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function getAllClubPlayers(string $clubId): Collection
    {
        return $this->model->newQuery()
            ->with(['player' => function ($query) {
                $query->select('id', 'name', 'profile_photo_url');
            }])
            ->where('club_id', $clubId)
            ->orderByRaw('ended_at IS NOT NULL')
            ->orderBy('started_at', 'desc')
            ->get();
    }

    public function getAvailablePlayers(string $clubId): Collection
    {
        $activeMemberIds = $this->model->newQuery()
            ->where('club_id', $clubId)
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
            ->where('club_id', $clubId)
            ->where('player_id', $validated['player_id'])
            ->whereNull('ended_at')
            ->exists();

        if ($alreadyActive) {
            throw new \RuntimeException('Player already has an active membership at this club.');
        }

        return $this->model->newQuery()->create([
            'club_id' => $clubId,
            'player_id' => $validated['player_id'],
            'started_at' => $validated['started_at'],
            'belt_rank' => $validated['belt_rank'] ?? null,
            'grade' => $validated['grade'] ?? null,
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
            'belt_rank' => $validated['belt_rank'] ?? null,
            'grade' => $validated['grade'] ?? null,
        ]);

        return $membership;
    }
}
