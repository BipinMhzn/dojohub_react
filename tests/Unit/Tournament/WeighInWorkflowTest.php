<?php

namespace Tests\Unit\Tournament;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Exceptions\WeighInNotAllowedException;
use App\Modules\Tournaments\Interfaces\WeighInInterface;
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

class WeighInWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): WeighInInterface
    {
        return app(WeighInInterface::class);
    }

    private function makeCategory(CategoryStatusEnum $status = CategoryStatusEnum::RegistrationClosed): TournamentCategory
    {
        $tournament = Tournament::factory()->create();
        $category = TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => 0,
            'age_category' => 8,
            'min_age' => 18,
            'max_age' => 34,
            'weight_category' => 0,
            'min_weight' => 60,
            'max_weight' => 90,
            'status' => $status,
        ]);

        return $category->load('tournament');
    }

    private function makeRegistration(TournamentCategory $category): TournamentRegistration
    {
        return TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => Player::factory()->create(['weight' => 80])->id,
            'player_age' => 25,
            'status' => RegistrationStatusEnum::Confirmed,
        ]);
    }

    public function test_weight_within_bounds_is_marked_made_weight(): void
    {
        $category = $this->makeCategory();
        $registration = $this->makeRegistration($category);

        $weighIn = $this->repository()->recordWeighIn($category, [
            'registration_id' => $registration->id,
            'weight' => 75,
        ]);

        $this->assertTrue($weighIn->made_weight);
    }

    public function test_weight_below_minimum_fails_to_make_weight(): void
    {
        $category = $this->makeCategory();
        $registration = $this->makeRegistration($category);

        $weighIn = $this->repository()->recordWeighIn($category, [
            'registration_id' => $registration->id,
            'weight' => 59.99,
        ]);

        $this->assertFalse($weighIn->made_weight);
    }

    public function test_weight_above_maximum_fails_to_make_weight(): void
    {
        $category = $this->makeCategory();
        $registration = $this->makeRegistration($category);

        $weighIn = $this->repository()->recordWeighIn($category, [
            'registration_id' => $registration->id,
            'weight' => 90.01,
        ]);

        $this->assertFalse($weighIn->made_weight);
    }

    public function test_multiple_attempts_are_all_retained_as_history(): void
    {
        $category = $this->makeCategory();
        $registration = $this->makeRegistration($category);

        $this->repository()->recordWeighIn($category, ['registration_id' => $registration->id, 'weight' => 100]);
        $this->repository()->recordWeighIn($category, ['registration_id' => $registration->id, 'weight' => 95]);
        $this->repository()->recordWeighIn($category, ['registration_id' => $registration->id, 'weight' => 88]);

        $this->assertSame(3, $registration->weighIns()->count());
        $this->assertTrue($registration->latestWeighIn->made_weight, 'The most recent attempt is the official weight.');
    }

    public function test_recording_a_weigh_in_syncs_the_players_live_weight(): void
    {
        $category = $this->makeCategory();
        $registration = $this->makeRegistration($category);

        $this->repository()->recordWeighIn($category, [
            'registration_id' => $registration->id,
            'weight' => 72.5,
        ]);

        $this->assertEquals(72.5, $registration->player->fresh()->weight);
    }

    public function test_weigh_in_allowed_when_bracket_generated(): void
    {
        $category = $this->makeCategory(CategoryStatusEnum::BracketGenerated);
        $registration = $this->makeRegistration($category);

        $weighIn = $this->repository()->recordWeighIn($category, [
            'registration_id' => $registration->id,
            'weight' => 75,
        ]);

        $this->assertNotNull($weighIn->id);
    }

    public function test_weigh_in_blocked_before_registration_is_closed(): void
    {
        $category = $this->makeCategory(CategoryStatusEnum::RegistrationOpen);
        $registration = $this->makeRegistration($category);

        $this->expectException(WeighInNotAllowedException::class);

        $this->repository()->recordWeighIn($category, [
            'registration_id' => $registration->id,
            'weight' => 75,
        ]);
    }

    public function test_weigh_in_blocked_in_draft(): void
    {
        $category = $this->makeCategory(CategoryStatusEnum::Draft);
        $registration = $this->makeRegistration($category);

        $this->expectException(WeighInNotAllowedException::class);

        $this->repository()->recordWeighIn($category, [
            'registration_id' => $registration->id,
            'weight' => 75,
        ]);
    }

    public function test_weigh_in_blocked_when_tournament_cancelled(): void
    {
        $category = $this->makeCategory();
        $category->tournament->update(['status' => TournamentStatusEnum::Cancelled]);
        $category->load('tournament');
        $registration = $this->makeRegistration($category);

        $this->expectException(TournamentCancelledException::class);

        $this->repository()->recordWeighIn($category, [
            'registration_id' => $registration->id,
            'weight' => 75,
        ]);
    }
}
