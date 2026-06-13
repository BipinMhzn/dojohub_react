<?php

namespace Tests\Unit\Tournament;

use App\Modules\Tournaments\Interfaces\TournamentStatusResolverInterface;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentStatusResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): TournamentStatusResolverInterface
    {
        return app(TournamentStatusResolverInterface::class);
    }

    private function makeCategory(Tournament $tournament, CategoryStatusEnum $status, int $division = 0): TournamentCategory
    {
        return TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => 0,
            'age_category' => 0,
            'min_age' => 18,
            'max_age' => 34,
            'weight_category' => $division,
            'min_weight' => 60,
            'max_weight' => 73,
            'status' => $status,
        ]);
    }

    public function test_cancelled_tournament_is_never_overridden(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Cancelled]);
        $this->makeCategory($tournament, CategoryStatusEnum::InProgress);

        $this->assertSame(TournamentStatusEnum::Cancelled, $this->resolver()->resolve($tournament));
    }

    public function test_no_categories_resolves_to_scheduled(): void
    {
        $tournament = Tournament::factory()->create();

        $this->assertSame(TournamentStatusEnum::Scheduled, $this->resolver()->resolve($tournament));
    }

    public function test_all_cancelled_categories_resolves_to_scheduled(): void
    {
        $tournament = Tournament::factory()->create();
        $this->makeCategory($tournament, CategoryStatusEnum::Cancelled, 0);
        $this->makeCategory($tournament, CategoryStatusEnum::Cancelled, 1);

        $this->assertSame(TournamentStatusEnum::Scheduled, $this->resolver()->resolve($tournament));
    }

    public function test_bracket_generated_makes_tournament_in_progress(): void
    {
        $tournament = Tournament::factory()->create();
        $this->makeCategory($tournament, CategoryStatusEnum::BracketGenerated, 0);
        $this->makeCategory($tournament, CategoryStatusEnum::Draft, 1);

        $this->assertSame(TournamentStatusEnum::InProgress, $this->resolver()->resolve($tournament));
    }

    public function test_in_progress_category_makes_tournament_in_progress(): void
    {
        $tournament = Tournament::factory()->create();
        $this->makeCategory($tournament, CategoryStatusEnum::Completed, 0);
        $this->makeCategory($tournament, CategoryStatusEnum::InProgress, 1);

        $this->assertSame(TournamentStatusEnum::InProgress, $this->resolver()->resolve($tournament));
    }

    public function test_all_completed_resolves_to_completed(): void
    {
        $tournament = Tournament::factory()->create();
        $this->makeCategory($tournament, CategoryStatusEnum::Completed, 0);
        $this->makeCategory($tournament, CategoryStatusEnum::Completed, 1);

        $this->assertSame(TournamentStatusEnum::Completed, $this->resolver()->resolve($tournament));
    }

    public function test_cancelled_category_is_ignored_when_others_completed(): void
    {
        $tournament = Tournament::factory()->create();
        $this->makeCategory($tournament, CategoryStatusEnum::Cancelled, 0);
        $this->makeCategory($tournament, CategoryStatusEnum::Completed, 1);

        $this->assertSame(TournamentStatusEnum::Completed, $this->resolver()->resolve($tournament));
    }

    public function test_completed_plus_registration_open_resolves_to_scheduled(): void
    {
        $tournament = Tournament::factory()->create();
        $this->makeCategory($tournament, CategoryStatusEnum::Completed, 0);
        $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen, 1);

        $this->assertSame(TournamentStatusEnum::Scheduled, $this->resolver()->resolve($tournament));
    }

    public function test_all_pre_bracket_resolves_to_scheduled(): void
    {
        $tournament = Tournament::factory()->create();
        $this->makeCategory($tournament, CategoryStatusEnum::Draft, 0);
        $this->makeCategory($tournament, CategoryStatusEnum::RegistrationClosed, 1);

        $this->assertSame(TournamentStatusEnum::Scheduled, $this->resolver()->resolve($tournament));
    }
}
