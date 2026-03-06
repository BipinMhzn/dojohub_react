<?php

namespace App\Modules\Tournaments\Services;

use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Models\TournamentMatch;
use App\System\Tournament\Foundation\Enums\MatchStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentTypeEnum;
use App\System\Tournament\Models\Tournament;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class BracketService
 */
class BracketService implements BracketServiceInterface
{
    /**
     * Generate bracket based on tournament type
     *
     * @throws Exception
     */
    public function generateBracket(Tournament $tournament): Collection
    {
        $seededPlayers = $this->seedPlayers($tournament);

        return DB::transaction(function () use ($tournament, $seededPlayers) {
            return match ($tournament->type) {
                TournamentTypeEnum::SINGLE_ELIMINATION => $this->generateSingleEliminationBracket($tournament, $seededPlayers),
                TournamentTypeEnum::DOUBLE_ELIMINATION => $this->generateDoubleEliminationBracket($tournament, $seededPlayers),
                TournamentTypeEnum::ROUND_ROBIN => $this->generateRoundRobinBracket($tournament, $seededPlayers),
            };
        });
    }

    /**
     * Seed players with top 3 separated
     *
     * @throws Exception
     */
    public function seedPlayers(Tournament $tournament): array
    {
        $registrations = $tournament->registrations()->with('player')->get();
        $playerCount = $registrations->count();

        if ($playerCount < 2) {
            throw new Exception('At least 2 players required for bracket generation');
        }

        // Take first 3 registrations as top seeds (in the order they registered)
        $topSeeds = $registrations->take(3)->values();

        // Shuffle remaining players randomly
        $remainingPlayers = $registrations->skip(3)->shuffle()->values();

        // Build seeded array
        $seeded = [];
        if ($topSeeds->has(0)) {
            $seeded[1] = $topSeeds->get(0); // Seed 1
        }
        if ($topSeeds->has(1)) {
            $seeded[2] = $topSeeds->get(1); // Seed 2
        }
        if ($topSeeds->has(2)) {
            $seeded[3] = $topSeeds->get(2); // Seed 3
        }

        // Fill remaining seeds randomly
        $currentSeed = 4;
        foreach ($remainingPlayers as $player) {
            $seeded[$currentSeed++] = $player;
        }

        return $seeded;
    }

    /**
     * Generate Single Elimination bracket
     */
    private function generateSingleEliminationBracket(Tournament $tournament, array $seededPlayers): Collection
    {
        $playerCount = count($seededPlayers);
        $bracketSize = $this->getNextPowerOfTwo($playerCount);
        $totalRounds = (int) log($bracketSize, 2);

        // Generate standard seeding pairs for first round
        $firstRoundPairs = $this->generateStandardSeeding($bracketSize);

        // Create all matches
        $matches = collect();

        // Create first round with actual players
        $roundMatches = [];
        foreach ($firstRoundPairs as $index => $pair) {
            [$seed1, $seed2] = $pair;

            $match = TournamentMatch::create([
                'tournament_id' => $tournament->id,
                'bracket_type' => 'main',
                'round_number' => 1,
                'round_name' => $this->getRoundName(1, $totalRounds, $bracketSize),
                'match_order' => $index + 1,
                'player1_id' => $seededPlayers[$seed1]->player_id ?? null,
                'player2_id' => isset($seededPlayers[$seed2]) ? $seededPlayers[$seed2]->player_id : null,
                'player1_seed' => $seed1,
                'player2_seed' => isset($seededPlayers[$seed2]) ? $seed2 : null,
                'status' => MatchStatusEnum::PENDING,
            ]);

            // Auto-complete bye matches
            if ($match->has_bye) {
                $match->winner_id = $match->player1_id;
                $match->status = MatchStatusEnum::COMPLETED;
                $match->completed_at = now();
                $match->save();
            }

            $roundMatches[] = $match;
            $matches->push($match);
        }

        // Create subsequent rounds (TBD matches)
        $previousRoundMatches = $roundMatches;
        for ($round = 2; $round <= $totalRounds; $round++) {
            $currentRoundMatches = [];
            $matchesInRound = count($previousRoundMatches) / 2;

            for ($i = 0; $i < $matchesInRound; $i++) {
                $match1 = $previousRoundMatches[$i * 2];
                $match2 = $previousRoundMatches[$i * 2 + 1];

                $match = TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'bracket_type' => 'main',
                    'round_number' => $round,
                    'round_name' => $this->getRoundName($round, $totalRounds, $bracketSize),
                    'match_order' => $i + 1,
                    'status' => MatchStatusEnum::PENDING,
                ]);

                // Link previous matches to this match
                $match1->update([
                    'winner_next_match_id' => $match->id,
                    'winner_position' => 1,
                ]);
                $match2->update([
                    'winner_next_match_id' => $match->id,
                    'winner_position' => 2,
                ]);

                // If both previous matches were byes, advance the winner
                if ($match1->has_bye && $match1->winner_id) {
                    $match->player1_id = $match1->winner_id;
                    $match->save();
                }
                if ($match2->has_bye && $match2->winner_id) {
                    $match->player2_id = $match2->winner_id;
                    $match->save();
                }

                $currentRoundMatches[] = $match;
                $matches->push($match);
            }
            $previousRoundMatches = $currentRoundMatches;
        }

        return $matches;
    }

    /**
     * Generate Double Elimination bracket (placeholder for future implementation)
     */
    private function generateDoubleEliminationBracket(Tournament $tournament, array $seededPlayers): Collection
    {
        // TODO: Implement double elimination logic
        // For now, generate single elimination
        return $this->generateSingleEliminationBracket($tournament, $seededPlayers);
    }

    /**
     * Generate Round Robin bracket (placeholder for future implementation)
     */
    private function generateRoundRobinBracket(Tournament $tournament, array $seededPlayers): Collection
    {
        // TODO: Implement round robin logic
        throw new Exception('Round Robin bracket generation not yet implemented');
    }

    /**
     * Generate standard bracket seeding pairs
     * This ensures seeds 1, 2, 3 are separated
     */
    private function generateStandardSeeding(int $bracketSize): array
    {
        $seeds = range(1, $bracketSize);
        $pairs = [];

        while (count($seeds) > 0) {
            $first = array_shift($seeds);
            $second = count($seeds) > 0 ? array_pop($seeds) : null;
            $pairs[] = [$first, $second];
        }

        return $pairs;
    }

    /**
     * Calculate next power of 2 for bracket size
     */
    private function getNextPowerOfTwo(int $number): int
    {
        return (int) pow(2, ceil(log($number, 2)));
    }

    /**
     * Get round name based on position in bracket
     */
    private function getRoundName(int $roundNumber, int $totalRounds, int $bracketSize): string
    {
        $matchesInRound = $bracketSize / pow(2, $roundNumber);

        if ($roundNumber === $totalRounds) {
            return 'Finals';
        } elseif ($roundNumber === $totalRounds - 1) {
            return 'Semifinals';
        } elseif ($roundNumber === $totalRounds - 2) {
            return 'Quarterfinals';
        } else {
            return 'Round of '.((int) $matchesInRound * 2);
        }
    }

    /**
     * Record match result and advance winner
     *
     * @throws Exception
     */
    public function recordMatchResult(string $matchId, string $winnerId, ?int $player1Score, ?int $player2Score): void
    {
        DB::transaction(function () use ($matchId, $winnerId, $player1Score, $player2Score) {
            $match = TournamentMatch::findOrFail($matchId);

            // Validate winner is a participant
            if ($winnerId !== $match->player1_id && $winnerId !== $match->player2_id) {
                throw new Exception('Winner must be one of the match participants');
            }

            // Update match result
            $match->update([
                'winner_id' => $winnerId,
                'player1_score' => $player1Score,
                'player2_score' => $player2Score,
                'status' => MatchStatusEnum::COMPLETED,
                'completed_at' => now(),
            ]);

            // Advance winner to next match
            if ($match->winner_next_match_id) {
                $nextMatch = TournamentMatch::find($match->winner_next_match_id);
                if ($nextMatch) {
                    if ($match->winner_position === 1) {
                        $nextMatch->update(['player1_id' => $winnerId]);
                    } else {
                        $nextMatch->update(['player2_id' => $winnerId]);
                    }
                }
            }

            // For double elimination, advance loser if applicable
            if ($match->loser_next_match_id) {
                $loserId = $winnerId === $match->player1_id ? $match->player2_id : $match->player1_id;
                $loserNextMatch = TournamentMatch::find($match->loser_next_match_id);
                if ($loserNextMatch && $loserId) {
                    if ($match->loser_position === 1) {
                        $loserNextMatch->update(['player1_id' => $loserId]);
                    } else {
                        $loserNextMatch->update(['player2_id' => $loserId]);
                    }
                }
            }
        });
    }

    /**
     * Schedule a match
     */
    public function scheduleMatch(string $matchId, string $scheduledAt): void
    {
        $match = TournamentMatch::findOrFail($matchId);
        $match->update([
            'scheduled_at' => $scheduledAt,
        ]);
    }

    /**
     * Get bracket structure for frontend
     */
    public function getBracketStructure(Tournament $tournament): array
    {
        $matches = $tournament->matches()
            ->with(['player1', 'player2', 'winner'])
            ->orderBy('round_number')
            ->orderBy('match_order')
            ->get();

        // Group by bracket type and round
        $structure = [
            'main' => [],
            'losers' => [],
        ];

        foreach ($matches as $match) {
            $bracketType = $match->bracket_type;
            $roundNumber = $match->round_number;

            if (! isset($structure[$bracketType][$roundNumber])) {
                $structure[$bracketType][$roundNumber] = [
                    'round_number' => $roundNumber,
                    'round_name' => $match->round_name,
                    'matches' => [],
                ];
            }

            $structure[$bracketType][$roundNumber]['matches'][] = [
                'id' => $match->id,
                'match_order' => $match->match_order,
                'player1' => $match->player1 ? [
                    'id' => $match->player1->id,
                    'name' => $match->player1->name,
                    'seed' => $match->player1_seed,
                    'score' => $match->player1_score,
                    'profile_photo' => $match->player1->profile_photo,
                ] : null,
                'player2' => $match->player2 ? [
                    'id' => $match->player2->id,
                    'name' => $match->player2->name,
                    'seed' => $match->player2_seed,
                    'score' => $match->player2_score,
                    'profile_photo' => $match->player2->profile_photo,
                ] : null,
                'winner_id' => $match->winner_id,
                'status' => $match->status->value,
                'scheduled_at' => $match->scheduled_at?->toISOString(),
                'has_bye' => $match->has_bye,
            ];
        }

        // Remove empty brackets and convert to arrays
        $structure = array_filter($structure, fn ($bracket) => ! empty($bracket));
        foreach ($structure as $key => $bracket) {
            $structure[$key] = array_values($bracket);
        }

        return [
            'type' => $tournament->type->value,
            'brackets' => $structure,
        ];
    }
}
