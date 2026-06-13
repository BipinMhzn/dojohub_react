<?php

namespace Tests\Unit\Tournament;

use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Interfaces\CategoryStatusTransitionServiceInterface;
use App\System\Club\Models\Club;
use App\System\Player\Models\Player;
use App\System\Tournament\Foundation\Enums\BracketStatusEnum;
use App\System\Tournament\Foundation\Enums\BracketTypeEnum;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\CompetitorResultEnum;
use App\System\Tournament\Foundation\Enums\MatchStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\SeedingMethodEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentBracket;
use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentMatch;
use App\System\Tournament\Models\TournamentRegistration;
use App\System\Tournament\Models\TournamentWeighIn;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BracketGenerationWorkflowTest extends TestCase
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

    private function makeRegistration(TournamentCategory $category): TournamentRegistration
    {
        return TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => Player::factory()->create()->id,
            'player_age' => 25,
            'status' => RegistrationStatusEnum::Confirmed,
        ]);
    }

    private function weighIn(TournamentRegistration $registration, float $weight, bool $madeWeight, string $weighedAt): TournamentWeighIn
    {
        return TournamentWeighIn::create([
            'registration_id' => $registration->id,
            'weight' => $weight,
            'weighed_at' => $weighedAt,
            'made_weight' => $madeWeight,
        ]);
    }

    private function makeBracket(TournamentCategory $category): TournamentBracket
    {
        return TournamentBracket::create([
            'tournament_category_id' => $category->id,
            'type' => BracketTypeEnum::SingleElimination,
            'seeding_method' => SeedingMethodEnum::Random,
            'status' => BracketStatusEnum::Pending,
        ]);
    }

    private function makeWeighedRegistration(TournamentCategory $category): TournamentRegistration
    {
        $registration = $this->makeRegistration($category);
        $this->weighIn($registration, 70, true, '2026-06-13 09:00:00');

        return $registration;
    }

    public function test_generation_blocked_when_a_competitor_is_not_weighed_in(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        $weighed = $this->makeRegistration($category);
        $this->weighIn($weighed, 70, true, '2026-06-13 09:00:00');
        $this->makeRegistration($category); // never weighed in

        $bracket = $this->makeBracket($category);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('1 competitor(s) still need to be weighed in');

        $this->service()->generateBracket($category, $bracket);
    }

    public function test_generation_blocked_when_a_competitor_has_not_made_weight(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        $passed = $this->makeRegistration($category);
        $this->weighIn($passed, 70, true, '2026-06-13 09:00:00');
        $failed = $this->makeRegistration($category);
        $this->weighIn($failed, 100, false, '2026-06-13 09:05:00');

        $bracket = $this->makeBracket($category);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('1 competitor(s) have not made weight');

        $this->service()->generateBracket($category, $bracket);
    }

    public function test_generation_uses_latest_weigh_in_so_an_earlier_pass_does_not_count(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        $other = $this->makeRegistration($category);
        $this->weighIn($other, 70, true, '2026-06-13 09:00:00');

        $regressed = $this->makeRegistration($category);
        $this->weighIn($regressed, 70, true, '2026-06-13 08:00:00');  // earlier pass
        $this->weighIn($regressed, 100, false, '2026-06-13 09:30:00'); // latest fails

        $bracket = $this->makeBracket($category);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('1 competitor(s) have not made weight');

        $this->service()->generateBracket($category, $bracket);
    }

    public function test_generation_succeeds_when_every_competitors_latest_weigh_in_passes(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        $first = $this->makeRegistration($category);
        $this->weighIn($first, 70, true, '2026-06-13 09:00:00');

        $second = $this->makeRegistration($category);
        $this->weighIn($second, 100, false, '2026-06-13 08:00:00'); // earlier fail
        $this->weighIn($second, 88, true, '2026-06-13 09:30:00');   // latest passes

        $bracket = $this->makeBracket($category);

        $matches = $this->service()->generateBracket($category, $bracket);

        $this->assertNotEmpty($matches);
        $this->assertSame(CategoryStatusEnum::BracketGenerated, $category->fresh()->status);
        $this->assertSame(BracketStatusEnum::Active, $bracket->fresh()->status);
    }

    public function test_readiness_reports_unmet_requirements(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->update(['status' => CategoryStatusEnum::RegistrationOpen]); // not closed
        $category->setRelation('tournament', $tournament);

        $this->makeWeighedRegistration($category); // only 1, weighed

        $readiness = $this->service()->getGenerationReadiness($category);

        $this->assertFalse($readiness['ready']);
        $byLabel = collect($readiness['requirements'])->keyBy('label');
        $this->assertFalse($byLabel['Registration is closed']['met']);
        $this->assertFalse($byLabel['At least 2 confirmed competitors']['met']);
        $this->assertTrue($byLabel['Every competitor has a passing weigh-in']['met']);
    }

    public function test_readiness_is_ready_when_all_requirements_met(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament); // RegistrationClosed
        $category->setRelation('tournament', $tournament);

        $this->makeWeighedRegistration($category);
        $this->makeWeighedRegistration($category);

        $readiness = $this->service()->getGenerationReadiness($category);

        $this->assertTrue($readiness['ready']);
        $this->assertTrue(collect($readiness['requirements'])->every(fn ($r): bool => $r['met']));
    }

    public function test_readiness_flags_incomplete_weigh_in(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        $this->makeWeighedRegistration($category);
        $this->makeRegistration($category); // confirmed but never weighed

        $readiness = $this->service()->getGenerationReadiness($category);

        $this->assertFalse($readiness['ready']);
        $weighIn = collect($readiness['requirements'])->firstWhere('label', 'Every competitor has a passing weigh-in');
        $this->assertFalse($weighIn['met']);
        $this->assertSame('1 of 2 made weight', $weighIn['detail']);
    }

    public function test_four_competitors_produce_two_first_round_matches_and_one_final(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        for ($i = 0; $i < 4; $i++) {
            $this->makeWeighedRegistration($category);
        }

        $bracket = $this->makeBracket($category);
        $this->service()->generateBracket($category, $bracket);

        $matches = TournamentMatch::query()->where('bracket_id', $bracket->id)->get();

        $this->assertCount(2, $matches->where('round_number', 1));
        $this->assertCount(1, $matches->where('round_number', 2));
        $this->assertSame(4, $bracket->fresh()->competitor_count);
        $this->assertSame(2, $bracket->fresh()->round_count);
    }

    public function test_odd_competitor_count_creates_an_auto_completed_bye(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        for ($i = 0; $i < 3; $i++) {
            $this->makeWeighedRegistration($category);
        }

        $bracket = $this->makeBracket($category);
        $this->service()->generateBracket($category, $bracket);

        $firstRound = TournamentMatch::query()
            ->where('bracket_id', $bracket->id)
            ->where('round_number', 1)
            ->with('competitors')
            ->get();

        $byeMatch = $firstRound->firstWhere('status', MatchStatusEnum::Completed);

        $this->assertNotNull($byeMatch, 'A bye should auto-complete one first-round match.');
        $this->assertCount(1, $byeMatch->competitors, 'A bye match has a single competitor.');
        $this->assertSame(CompetitorResultEnum::WalkoverWin, $byeMatch->competitors->first()->result);
    }

    public function test_fewer_than_two_confirmed_registrations_throws(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        $this->makeWeighedRegistration($category);

        $bracket = $this->makeBracket($category);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('At least 2 confirmed registrations');

        $this->service()->generateBracket($category, $bracket);
    }

    public function test_generation_blocked_when_tournament_cancelled(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Cancelled]);
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        $this->makeWeighedRegistration($category);
        $this->makeWeighedRegistration($category);

        $bracket = $this->makeBracket($category);

        $this->expectException(\App\Modules\Tournaments\Exceptions\TournamentCancelledException::class);

        $this->service()->generateBracket($category, $bracket);
    }

    public function test_generation_blocked_when_registration_not_closed(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->update(['status' => CategoryStatusEnum::RegistrationOpen]);
        $category->setRelation('tournament', $tournament);

        $this->makeWeighedRegistration($category);
        $this->makeWeighedRegistration($category);

        $bracket = $this->makeBracket($category);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Close registration before generating a bracket');

        $this->service()->generateBracket($category, $bracket);
    }

    public function test_regeneration_after_reset_rebuilds_a_fresh_bracket(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $category->setRelation('tournament', $tournament);

        for ($i = 0; $i < 4; $i++) {
            $this->makeWeighedRegistration($category);
        }

        $firstBracket = $this->makeBracket($category);
        $this->service()->generateBracket($category, $firstBracket);

        // Regeneration reset (§7.5): BracketGenerated → RegistrationClosed soft-deletes the bracket + matches.
        app(CategoryStatusTransitionServiceInterface::class)
            ->transitionTo($category->fresh(), CategoryStatusEnum::RegistrationClosed);

        $this->assertSoftDeleted('tournament_brackets', ['id' => $firstBracket->id]);
        $this->assertSame(CategoryStatusEnum::RegistrationClosed, $category->fresh()->status);

        $secondBracket = $this->makeBracket($category->fresh());
        $this->service()->generateBracket($category->fresh(), $secondBracket);

        $this->assertSame(CategoryStatusEnum::BracketGenerated, $category->fresh()->status);
        $this->assertSame(1, $category->fresh()->brackets()->count(), 'Only the new bracket should be active.');
        $this->assertTrue(
            TournamentMatch::query()->where('bracket_id', $secondBracket->id)->exists(),
            'The regenerated bracket should have fresh matches.',
        );
    }
}
