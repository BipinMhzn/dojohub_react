<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\Tournaments\Database\Models\TournamentMatch;
use App\Modules\Tournaments\Interfaces\MatchInterface;
use App\System\Tournament\Database\Models\Tournament;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Class MatchRepository
 * @package App\Modules\Tournaments\Repositories
 */
final class MatchRepository extends BaseRepository implements MatchInterface
{
    /**
     * MatchRepository constructor.
     * @param TournamentMatch $match
     */
    public function __construct(
        private readonly TournamentMatch $match,
    ) {
        parent::__construct($match);
    }

    /**
     * @param Builder $builder
     */
    public function getFilter(Builder $builder): void
    {
        // Add any filtering logic if needed
    }

    /**
     * Get a single match by ID with relationships
     *
     * @param string $matchId
     * @return \App\System\Tournament\Database\Models\TournamentMatch
     */
    public function getMatchById(string $matchId): \App\System\Tournament\Database\Models\TournamentMatch
    {
        return $this->match->newQuery()
            ->with(['player1', 'player2', 'winner', 'tournament'])
            ->findOrFail($matchId);
    }

    /**
     * Get all matches for a tournament
     *
     * @param Tournament $tournament
     * @return Collection
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
     *
     * @param string $matchId
     * @param array $inputs
     * @return \App\System\Tournament\Database\Models\TournamentMatch
     */
    public function updateMatchResult(string $matchId, array $inputs): \App\System\Tournament\Database\Models\TournamentMatch
    {
        $match = $this->getMatchById($matchId);
        $match->fill($inputs);
        $match->save();

        return $match;
    }

    /**
     * Update match schedule
     *
     * @param string $matchId
     * @param string $scheduledAt
     * @return \App\System\Tournament\Database\Models\TournamentMatch
     */
    public function updateMatchSchedule(string $matchId, string $scheduledAt): \App\System\Tournament\Database\Models\TournamentMatch
    {
        $match = $this->getMatchById($matchId);
        $match->scheduled_at = $scheduledAt;
        $match->save();

        return $match;
    }
}
