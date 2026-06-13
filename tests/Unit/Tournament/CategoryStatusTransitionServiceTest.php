<?php

namespace Tests\Unit\Tournament;

use App\Modules\Tournaments\Exceptions\InvalidCategoryStatusTransitionException;
use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\CategoryStatusTransitionServiceInterface;
use App\System\Club\Models\Club;
use App\System\Player\Models\Player;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryStatusTransitionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CategoryStatusTransitionServiceInterface
    {
        return app(CategoryStatusTransitionServiceInterface::class);
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

    private function makeRegistration(TournamentCategory $category, RegistrationStatusEnum $status): TournamentRegistration
    {
        return TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => Player::factory()->create()->id,
            'player_age' => 25,
            'status' => $status,
        ]);
    }

    public function test_valid_transition_updates_status(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament, CategoryStatusEnum::Draft);

        $this->service()->transitionTo($category, CategoryStatusEnum::RegistrationOpen);

        $this->assertSame(CategoryStatusEnum::RegistrationOpen, $category->fresh()->status);
    }

    public function test_invalid_transition_throws_and_leaves_status_unchanged(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament, CategoryStatusEnum::Draft);

        try {
            $this->service()->transitionTo($category, CategoryStatusEnum::Completed);
            $this->fail('Expected InvalidCategoryStatusTransitionException.');
        } catch (InvalidCategoryStatusTransitionException $e) {
            // expected
        }

        $this->assertSame(CategoryStatusEnum::Draft, $category->fresh()->status);
    }

    public function test_transition_blocked_when_tournament_cancelled(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Cancelled]);
        $category = $this->makeCategory($tournament, CategoryStatusEnum::Draft);

        $this->expectException(TournamentCancelledException::class);

        $this->service()->transitionTo($category, CategoryStatusEnum::RegistrationOpen);
    }

    public function test_closing_registration_confirms_registered_only(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen);

        $regA = $this->makeRegistration($category, RegistrationStatusEnum::Registered);
        $regB = $this->makeRegistration($category, RegistrationStatusEnum::Registered);
        $withdrawn = $this->makeRegistration($category, RegistrationStatusEnum::Withdrawn);

        $this->service()->transitionTo($category, CategoryStatusEnum::RegistrationClosed);

        $this->assertSame(RegistrationStatusEnum::Confirmed, $regA->fresh()->status);
        $this->assertSame(RegistrationStatusEnum::Confirmed, $regB->fresh()->status);
        $this->assertSame(RegistrationStatusEnum::Withdrawn, $withdrawn->fresh()->status);
    }

    public function test_tournament_status_synced_after_category_transition(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Scheduled]);
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationClosed);

        $this->service()->transitionTo($category, CategoryStatusEnum::BracketGenerated);

        $this->assertSame(TournamentStatusEnum::InProgress, $tournament->fresh()->status);
    }

    public function test_cancelling_only_category_resets_tournament_to_scheduled(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::InProgress]);
        $category = $this->makeCategory($tournament, CategoryStatusEnum::InProgress);

        $this->service()->transitionTo($category, CategoryStatusEnum::Cancelled);

        $this->assertSame(TournamentStatusEnum::Scheduled, $tournament->fresh()->status);
    }

    public function test_terminal_states_have_no_allowed_targets(): void
    {
        $this->assertSame([], $this->service()->allowedTargets(CategoryStatusEnum::Completed));
        $this->assertSame([], $this->service()->allowedTargets(CategoryStatusEnum::Cancelled));
    }
}
