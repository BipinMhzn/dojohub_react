<?php

namespace App\Modules\Tournaments\Interfaces;

use App\System\Tournament\Models\Tournament;
use Illuminate\Support\Collection;

interface BracketServiceInterface
{
    /**
     * Generate bracket based on tournament type
     */
    public function generateBracket(Tournament $tournament): Collection;

    /**
     * Seed players with top 3 separated
     */
    public function seedPlayers(Tournament $tournament): array;

    /**
     * Record match result and advance winner
     */
    public function recordMatchResult(string $matchId, string $winnerId, ?int $player1Score, ?int $player2Score): void;

    /**
     * Schedule a match
     */
    public function scheduleMatch(string $matchId, string $scheduledAt): void;

    /**
     * Get bracket structure for frontend
     */
    public function getBracketStructure(Tournament $tournament): array;
}
