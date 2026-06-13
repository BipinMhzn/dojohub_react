<?php

namespace Tests\Unit\Tournament;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\System\Club\Models\Club;
use App\System\Player\Models\Player;
use App\System\Tournament\Foundation\Enums\BracketStatusEnum;
use App\System\Tournament\Foundation\Enums\BracketTypeEnum;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\CompetitorPositionEnum;
use App\System\Tournament\Foundation\Enums\CompetitorResultEnum;
use App\System\Tournament\Foundation\Enums\MatchStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\SeedingMethodEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentBracket;
use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentMatch;
use App\System\Tournament\Models\TournamentRegistration;
use App\System\Tournament\Models\TournamentResult;
use App\System\Tournament\Models\TournamentWeighIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchExecutionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BracketServiceInterface
    {
        return app(BracketServiceInterface::class);
    }

    private function makeCategory(Tournament $tournament): TournamentCategory
    {
        return TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => 0,
            'age_category' => 8,
            'min_age' => 18,
            'max_age' => 34,
            'weight_category' => 0,
            'min_weight' => 60,
            'max_weight' => 90,
            'status' => CategoryStatusEnum::RegistrationClosed,
        ]);
    }

    private function makeConfirmedRegistration(TournamentCategory $category): TournamentRegistration
    {
        $registration = TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => Player::factory()->create()->id,
            'player_age' => 25,
            'status' => RegistrationStatusEnum::Confirmed,
        ]);

        TournamentWeighIn::create([
            'registration_id' => $registration->id,
            'weight' => 70,
            'weighed_at' => '2026-06-13 09:00:00',
            'made_weight' => true,
        ]);

        return $registration;
    }

    /**
     * Build a 4-competitor SingleElimination bracket: 2 first-round matches feeding one final.
     */
    private function generateFourPersonBracket(): TournamentCategory
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        for ($i = 0; $i < 4; $i++) {
            $this->makeConfirmedRegistration($category);
        }

        $bracket = TournamentBracket::create([
            'tournament_category_id' => $category->id,
            'type' => BracketTypeEnum::SingleElimination,
            'seeding_method' => SeedingMethodEnum::Random,
            'status' => BracketStatusEnum::Pending,
        ]);

        $this->service()->generateBracket($category, $bracket);

        return $category->fresh();
    }

    private function firstRoundMatches(TournamentCategory $category): \Illuminate\Support\Collection
    {
        return TournamentMatch::query()
            ->whereIn('bracket_id', $category->brackets()->pluck('id'))
            ->where('round_number', 1)
            ->orderBy('match_number')
            ->get();
    }

    private function finalMatch(TournamentCategory $category): TournamentMatch
    {
        return TournamentMatch::query()
            ->whereIn('bracket_id', $category->brackets()->pluck('id'))
            ->whereNull('winner_next_match_id')
            ->orderByDesc('round_number')
            ->firstOrFail();
    }

    private function winByWhite(TournamentMatch $match): void
    {
        $this->service()->recordMatchResult(
            $match->id,
            CompetitorPositionEnum::White->value,
            10, 0, 1, 0, null,
        );
    }

    public function test_recording_a_result_completes_the_match_and_sets_competitor_outcomes(): void
    {
        $category = $this->generateFourPersonBracket();
        $match = $this->firstRoundMatches($category)->first();

        $this->winByWhite($match);

        $match->refresh()->load('competitors');
        $this->assertSame(MatchStatusEnum::Completed, $match->status);
        $this->assertNotNull($match->winning_competitor_id);
        $this->assertNotNull($match->completed_at);

        $white = $match->competitors->firstWhere('position', CompetitorPositionEnum::White);
        $blue = $match->competitors->firstWhere('position', CompetitorPositionEnum::Blue);
        $this->assertSame(CompetitorResultEnum::Win, $white->result);
        $this->assertSame(CompetitorResultEnum::Loss, $blue->result);
        $this->assertSame($white->id, $match->winning_competitor_id);
    }

    public function test_first_result_transitions_category_to_in_progress(): void
    {
        $category = $this->generateFourPersonBracket();
        $this->assertSame(CategoryStatusEnum::BracketGenerated, $category->status);

        $this->winByWhite($this->firstRoundMatches($category)->first());

        $this->assertSame(CategoryStatusEnum::InProgress, $category->fresh()->status);
    }

    public function test_winner_is_propagated_into_the_next_match(): void
    {
        $category = $this->generateFourPersonBracket();
        $firstMatch = $this->firstRoundMatches($category)->first();

        $winningRegistrationId = $firstMatch->load('competitors')
            ->competitors->firstWhere('position', CompetitorPositionEnum::White)->registration_id;

        $this->winByWhite($firstMatch);

        $final = $this->finalMatch($category)->load('competitors');
        $this->assertTrue(
            $final->competitors->contains('registration_id', $winningRegistrationId),
            'Winner of the first-round match should appear in the final.',
        );
    }

    public function test_completing_all_matches_completes_the_category_and_generates_results(): void
    {
        $category = $this->generateFourPersonBracket();

        foreach ($this->firstRoundMatches($category) as $match) {
            $this->winByWhite($match);
        }

        $this->winByWhite($this->finalMatch($category));

        $this->assertSame(CategoryStatusEnum::Completed, $category->fresh()->status);

        $results = TournamentResult::query()
            ->whereIn('registration_id', $category->registrations()->pluck('id'))
            ->get();

        $this->assertCount(4, $results, 'Gold, silver and two bronze positions expected.');
        $this->assertSame(1, $results->where('position', 1)->count());
        $this->assertSame(1, $results->where('position', 2)->count());
        $this->assertSame(2, $results->where('position', 3)->count());
    }

    public function test_result_recording_blocked_when_tournament_cancelled(): void
    {
        $category = $this->generateFourPersonBracket();
        $match = $this->firstRoundMatches($category)->first();

        $category->tournament->update(['status' => \App\System\Tournament\Foundation\Enums\TournamentStatusEnum::Cancelled]);

        $this->expectException(TournamentCancelledException::class);

        $this->winByWhite($match);
    }

    public function test_result_recording_requires_both_competitors(): void
    {
        $category = $this->generateFourPersonBracket();
        $final = $this->finalMatch($category); // empty until first-round winners propagate

        $this->expectExceptionMessage('Match must have both competitors');

        $this->winByWhite($final);
    }
}
