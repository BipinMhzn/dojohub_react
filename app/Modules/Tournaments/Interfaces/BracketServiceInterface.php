<?php

namespace App\Modules\Tournaments\Interfaces;

use App\System\Tournament\Database\Models\Tournament;
use Illuminate\Support\Collection;

interface BracketServiceInterface
{
    /**
     * Generate bracket based on tournament type
     *
     * @param Tournament $tournament
     * @return Collection
     */
    public function generateBracket(Tournament $tournament): Collection;

    /**
     * Seed players with top 3 separated
     *
     * @param Tournament $tournament
     * @return array
     */
    public function seedPlayers(Tournament $tournament): array;

    /**
     * Record match result and advance winner
     *
     * @param string $matchId
     * @param string $winnerId
     * @param int|null $player1Score
     * @param int|null $player2Score
     * @return void
     */
    public function recordMatchResult(string $matchId, string $winnerId, ?int $player1Score, ?int $player2Score): void;

    /**
     * Schedule a match
     *
     * @param string $matchId
     * @param string $scheduledAt
     * @return void
     */
    public function scheduleMatch(string $matchId, string $scheduledAt): void;

    /**
     * Get bracket structure for frontend
     *
     * @param Tournament $tournament
     * @return array
     */
    public function getBracketStructure(Tournament $tournament): array;
}
