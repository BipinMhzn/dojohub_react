<?php

namespace App\Modules\Tournaments\Interfaces;

use App\System\Tournament\Models\TournamentBracket;
use App\System\Tournament\Models\TournamentCategory;
use Illuminate\Support\Collection;

/**
 * Interface BracketServiceInterface
 */
interface BracketServiceInterface
{
    /**
     * Generate bracket for a category
     */
    public function generateBracket(TournamentCategory $category, TournamentBracket $bracket): Collection;

    /**
     * Seed players from category registrations
     */
    public function seedRegistrations(TournamentCategory $category): array;

    /**
     * Record match result via competitor scores and winner position
     */
    public function recordMatchResult(string $matchId, int $winnerPosition, ?int $whiteScore, ?int $whitePenalties, ?int $blueScore, ?int $bluePenalties, ?string $notes): void;

    /**
     * Schedule a match
     */
    public function scheduleMatch(string $matchId, string $scheduledAt): void;

    /**
     * Get bracket structure for frontend
     */
    public function getBracketStructure(TournamentBracket $bracket): array;
}
