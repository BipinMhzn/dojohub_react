<?php

namespace App\Modules\Tournaments\Services;

use App\Modules\TournamentCategories\Models\TournamentCategory;
use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Models\TournamentBracket;
use App\Modules\Tournaments\Models\TournamentMatch;
use App\Modules\Tournaments\Models\TournamentMatchCompetitor;
use App\System\Tournament\Foundation\Enums\BracketSectionEnum;
use App\System\Tournament\Foundation\Enums\BracketStatusEnum;
use App\System\Tournament\Foundation\Enums\BracketTypeEnum;
use App\System\Tournament\Foundation\Enums\CompetitorPositionEnum;
use App\System\Tournament\Foundation\Enums\CompetitorResultEnum;
use App\System\Tournament\Foundation\Enums\MatchStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class BracketService
 */
class BracketService implements BracketServiceInterface
{
    /**
     * @throws Exception
     */
    public function generateBracket(TournamentCategory $category, TournamentBracket $bracket): Collection
    {
        $seededRegistrations = $this->seedRegistrations($category);

        return DB::transaction(function () use ($bracket, $seededRegistrations) {
            $matches = match ($bracket->type) {
                BracketTypeEnum::SingleElimination => $this->generateSingleElimination($bracket, $seededRegistrations),
                BracketTypeEnum::DoubleElimination => $this->generateSingleElimination($bracket, $seededRegistrations),
                default => throw new Exception('Bracket type not yet implemented'),
            };

            $bracket->update([
                'competitor_count' => count($seededRegistrations),
                'round_count' => (int) log($this->getNextPowerOfTwo(count($seededRegistrations)), 2),
                'status' => BracketStatusEnum::Active,
                'generated_at' => now(),
            ]);

            return $matches;
        });
    }

    /**
     * @throws Exception
     */
    public function seedRegistrations(TournamentCategory $category): array
    {
        $registrations = $category->registrations()
            ->where('status', '!=', RegistrationStatusEnum::Withdrawn)
            ->with('player')
            ->get();

        $count = $registrations->count();

        if ($count < 2) {
            throw new Exception('At least 2 active registrations are required for bracket generation');
        }

        $topSeeds = $registrations->take(3)->values();
        $remainingRegistrations = $registrations->skip(3)->shuffle()->values();

        $seeded = [];
        foreach ($topSeeds as $index => $registration) {
            $seeded[$index + 1] = $registration;
        }

        $currentSeed = count($topSeeds) + 1;
        foreach ($remainingRegistrations as $registration) {
            $seeded[$currentSeed++] = $registration;
        }

        return $seeded;
    }

    private function generateSingleElimination(TournamentBracket $bracket, array $seededRegistrations): Collection
    {
        $playerCount = count($seededRegistrations);
        $bracketSize = $this->getNextPowerOfTwo($playerCount);
        $totalRounds = (int) log($bracketSize, 2);
        $firstRoundPairs = $this->generateStandardSeeding($bracketSize);

        $matches = collect();
        $roundMatches = [];

        foreach ($firstRoundPairs as $index => $pair) {
            [$seed1, $seed2] = $pair;

            $reg1 = $seededRegistrations[$seed1] ?? null;
            $reg2 = isset($seededRegistrations[$seed2]) ? $seededRegistrations[$seed2] : null;

            $match = TournamentMatch::create([
                'bracket_id' => $bracket->id,
                'bracket_section' => BracketSectionEnum::Main,
                'round_number' => 1,
                'round_name' => $this->getRoundName(1, $totalRounds, $bracketSize),
                'match_number' => $index + 1,
                'status' => MatchStatusEnum::Pending,
            ]);

            if ($reg1) {
                TournamentMatchCompetitor::create([
                    'match_id' => $match->id,
                    'registration_id' => $reg1->id,
                    'position' => CompetitorPositionEnum::White,
                    'seed' => $seed1,
                    'result' => CompetitorResultEnum::Pending,
                ]);
            }

            if ($reg2) {
                TournamentMatchCompetitor::create([
                    'match_id' => $match->id,
                    'registration_id' => $reg2->id,
                    'position' => CompetitorPositionEnum::Blue,
                    'seed' => $seed2,
                    'result' => CompetitorResultEnum::Pending,
                ]);
            }

            // Auto-complete bye: only White (reg1) exists, no opponent
            if ($reg1 && ! $reg2) {
                $match->update([
                    'winner_id' => $reg1->player_id,
                    'status' => MatchStatusEnum::Completed,
                    'completed_at' => now(),
                ]);
                TournamentMatchCompetitor::where('match_id', $match->id)
                    ->where('position', CompetitorPositionEnum::White)
                    ->update(['result' => CompetitorResultEnum::WalkoverWin]);
            }

            $roundMatches[] = $match;
            $matches->push($match);
        }

        $previousRoundMatches = $roundMatches;
        for ($round = 2; $round <= $totalRounds; $round++) {
            $currentRoundMatches = [];
            $matchesInRound = count($previousRoundMatches) / 2;
            $section = ($round === $totalRounds) ? BracketSectionEnum::GoldFinal : BracketSectionEnum::Main;

            for ($i = 0; $i < $matchesInRound; $i++) {
                $match1 = $previousRoundMatches[$i * 2];
                $match2 = $previousRoundMatches[$i * 2 + 1];

                $match = TournamentMatch::create([
                    'bracket_id' => $bracket->id,
                    'bracket_section' => $section,
                    'round_number' => $round,
                    'round_name' => $this->getRoundName($round, $totalRounds, $bracketSize),
                    'match_number' => $i + 1,
                    'status' => MatchStatusEnum::Pending,
                ]);

                $match1->update([
                    'winner_next_match_id' => $match->id,
                    'winner_next_position' => 1,
                ]);
                $match2->update([
                    'winner_next_match_id' => $match->id,
                    'winner_next_position' => 2,
                ]);

                // Advance bye winners automatically
                $this->advanceByeWinner($match1, $match, 1);
                $this->advanceByeWinner($match2, $match, 2);

                $currentRoundMatches[] = $match;
                $matches->push($match);
            }

            $previousRoundMatches = $currentRoundMatches;
        }

        return $matches;
    }

    private function advanceByeWinner(TournamentMatch $byeMatch, TournamentMatch $nextMatch, int $position): void
    {
        if ($byeMatch->status !== MatchStatusEnum::Completed || ! $byeMatch->winner_id) {
            return;
        }

        // Find the registration for the winner
        $winnerCompetitor = TournamentMatchCompetitor::where('match_id', $byeMatch->id)
            ->where('result', CompetitorResultEnum::WalkoverWin)
            ->first();

        if (! $winnerCompetitor) {
            return;
        }

        $competitorPosition = $position === 1 ? CompetitorPositionEnum::White : CompetitorPositionEnum::Blue;

        TournamentMatchCompetitor::create([
            'match_id' => $nextMatch->id,
            'registration_id' => $winnerCompetitor->registration_id,
            'position' => $competitorPosition,
            'seed' => $winnerCompetitor->seed,
            'result' => CompetitorResultEnum::Pending,
        ]);
    }

    /**
     * @throws Exception
     */
    public function recordMatchResult(
        string $matchId,
        int $winnerPosition,
        ?int $whiteScore,
        ?int $whitePenalties,
        ?int $blueScore,
        ?int $bluePenalties,
        ?string $notes
    ): void {
        DB::transaction(function () use ($matchId, $winnerPosition, $whiteScore, $whitePenalties, $blueScore, $bluePenalties, $notes) {
            $match = TournamentMatch::with('competitors.registration')->findOrFail($matchId);

            $whiteCompetitor = $match->competitors->firstWhere('position', CompetitorPositionEnum::White);
            $blueCompetitor = $match->competitors->firstWhere('position', CompetitorPositionEnum::Blue);

            if (! $whiteCompetitor || ! $blueCompetitor) {
                throw new Exception('Match must have both competitors before recording a result');
            }

            $winnerCompetitor = $winnerPosition === CompetitorPositionEnum::White->value ? $whiteCompetitor : $blueCompetitor;
            $loserCompetitor = $winnerPosition === CompetitorPositionEnum::White->value ? $blueCompetitor : $whiteCompetitor;

            $whiteCompetitor->update([
                'score' => $whiteScore,
                'penalties' => $whitePenalties,
                'result' => $winnerCompetitor->id === $whiteCompetitor->id
                    ? CompetitorResultEnum::Win
                    : CompetitorResultEnum::Loss,
            ]);

            $blueCompetitor->update([
                'score' => $blueScore,
                'penalties' => $bluePenalties,
                'result' => $winnerCompetitor->id === $blueCompetitor->id
                    ? CompetitorResultEnum::Win
                    : CompetitorResultEnum::Loss,
            ]);

            $match->update([
                'winner_id' => $winnerCompetitor->registration->player_id,
                'status' => MatchStatusEnum::Completed,
                'completed_at' => now(),
                'notes' => $notes,
            ]);

            // Advance winner to next match
            if ($match->winner_next_match_id) {
                $nextMatch = TournamentMatch::find($match->winner_next_match_id);
                if ($nextMatch) {
                    $nextPosition = $match->winner_next_position === 1
                        ? CompetitorPositionEnum::White
                        : CompetitorPositionEnum::Blue;

                    TournamentMatchCompetitor::create([
                        'match_id' => $nextMatch->id,
                        'registration_id' => $winnerCompetitor->registration_id,
                        'position' => $nextPosition,
                        'seed' => $winnerCompetitor->seed,
                        'result' => CompetitorResultEnum::Pending,
                    ]);
                }
            }

            // Advance loser to repechage if applicable
            if ($match->loser_next_match_id) {
                $loserNextMatch = TournamentMatch::find($match->loser_next_match_id);
                if ($loserNextMatch) {
                    $loserPosition = $match->loser_next_position === 1
                        ? CompetitorPositionEnum::White
                        : CompetitorPositionEnum::Blue;

                    TournamentMatchCompetitor::create([
                        'match_id' => $loserNextMatch->id,
                        'registration_id' => $loserCompetitor->registration_id,
                        'position' => $loserPosition,
                        'seed' => $loserCompetitor->seed,
                        'result' => CompetitorResultEnum::Pending,
                    ]);
                }
            }
        });
    }

    public function scheduleMatch(string $matchId, string $scheduledAt): void
    {
        $match = TournamentMatch::findOrFail($matchId);
        $match->update(['scheduled_at' => $scheduledAt]);
    }

    public function getBracketStructure(TournamentBracket $bracket): array
    {
        $matches = $bracket->matches()
            ->with(['competitors.registration.player', 'competitors.registration.club', 'winner'])
            ->orderBy('bracket_section')
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get();

        $structure = [];

        foreach ($matches as $match) {
            $section = $match->bracket_section->name;
            $round = $match->round_number;

            if (! isset($structure[$section])) {
                $structure[$section] = [];
            }

            if (! isset($structure[$section][$round])) {
                $structure[$section][$round] = [
                    'round_number' => $round,
                    'round_name' => $match->round_name,
                    'matches' => [],
                ];
            }

            $white = $match->competitors->firstWhere('position', CompetitorPositionEnum::White);
            $blue = $match->competitors->firstWhere('position', CompetitorPositionEnum::Blue);

            $structure[$section][$round]['matches'][] = [
                'id' => $match->id,
                'match_number' => $match->match_number,
                'white' => $white ? [
                    'registration_id' => $white->registration_id,
                    'player_id' => $white->registration->player_id,
                    'name' => $white->registration->player->name,
                    'seed' => $white->seed,
                    'score' => $white->score,
                    'penalties' => $white->penalties,
                    'result' => $white->result->value,
                    'profile_photo_url' => $white->registration->player->profile_photo_url,
                ] : null,
                'blue' => $blue ? [
                    'registration_id' => $blue->registration_id,
                    'player_id' => $blue->registration->player_id,
                    'name' => $blue->registration->player->name,
                    'seed' => $blue->seed,
                    'score' => $blue->score,
                    'penalties' => $blue->penalties,
                    'result' => $blue->result->value,
                    'profile_photo_url' => $blue->registration->player->profile_photo_url,
                ] : null,
                'winner_id' => $match->winner_id,
                'status' => $match->status->value,
                'scheduled_at' => $match->scheduled_at?->toISOString(),
                'notes' => $match->notes,
            ];
        }

        // Flatten round arrays to sequential arrays
        foreach ($structure as $section => $rounds) {
            $structure[$section] = array_values($rounds);
        }

        return [
            'id' => $bracket->id,
            'type' => $bracket->type->value,
            'status' => $bracket->status->value,
            'sections' => $structure,
        ];
    }

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

    private function getNextPowerOfTwo(int $number): int
    {
        if ($number <= 1) {
            return 2;
        }

        return (int) pow(2, ceil(log($number, 2)));
    }

    private function getRoundName(int $roundNumber, int $totalRounds, int $bracketSize): string
    {
        if ($roundNumber === $totalRounds) {
            return 'Final';
        } elseif ($roundNumber === $totalRounds - 1) {
            return 'Semifinal';
        } elseif ($roundNumber === $totalRounds - 2) {
            return 'Quarterfinal';
        } else {
            $matchesInRound = (int) ($bracketSize / pow(2, $roundNumber));

            return 'Round of '.($matchesInRound * 2);
        }
    }
}
