<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\Tournaments\Interfaces\MatchInterface;
use App\Modules\Tournaments\Models\TournamentMatch;
use App\System\Tournament\Models\Tournament;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Class MatchRepository
 */
final class MatchRepository extends BaseRepository implements MatchInterface
{
    /**
     * MatchRepository constructor.
     */
    public function __construct(
        private readonly TournamentMatch $match,
    ) {
        parent::__construct($match);
    }

    public function getFilter(Builder $builder): void
    {
        // Add any filtering logic if needed
    }

    /**
     * Get a single match by ID with relationships
     */
    public function getMatchById(string $matchId): \App\System\Tournament\Models\TournamentMatch
    {
        return $this->match->newQuery()
            ->with(['player1', 'player2', 'winner', 'tournament'])
            ->findOrFail($matchId);
    }

    /**
     * Get all matches for a tournament
     */
    public function getTournamentMatches(Tournament $tournament): Collection
    {
        return $this->match->newQuery()
            ->where('tournament_id', $tournament->id)
            ->with(['player1', 'player2', 'winner'])
            ->orderBy('round_number')
            ->orderBy('match_order')
            ->get();
    }

    /**
     * Update match result
     */
    public function updateMatchResult(string $matchId, array $inputs): \App\System\Tournament\Models\TournamentMatch
    {
        $match = $this->getMatchById($matchId);
        $match->fill($inputs);
        $match->save();

        return $match;
    }

    /**
     * Update match schedule
     */
    public function updateMatchSchedule(string $matchId, string $scheduledAt): \App\System\Tournament\Models\TournamentMatch
    {
        $match = $this->getMatchById($matchId);
        $match->scheduled_at = $scheduledAt;
        $match->save();

        return $match;
    }
}
