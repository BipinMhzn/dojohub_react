<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\Tournaments\Interfaces\MatchInterface;
use App\Modules\Tournaments\Models\TournamentMatch;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Class MatchRepository
 */
final class MatchRepository extends BaseRepository implements MatchInterface
{
    public function __construct(TournamentMatch $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): void {}

    public function getMatchById(string $matchId): TournamentMatch
    {
        return $this->model->newQuery()
            ->with(['competitors.registration.player', 'competitors.registration.club'])
            ->findOrFail($matchId);
    }

    public function getBracketMatches(string $bracketId): Collection
    {
        return $this->model->newQuery()
            ->where('bracket_id', $bracketId)
            ->with(['competitors.registration.player', 'competitors.registration.club', 'winner'])
            ->orderBy('bracket_section')
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get();
    }

    public function updateMatchSchedule(string $matchId, string $scheduledAt): TournamentMatch
    {
        $match = $this->model->newQuery()->findOrFail($matchId);
        $match->update(['scheduled_at' => $scheduledAt]);

        return $match;
    }
}
