<?php

namespace Tests\Feature;

use App\System\Club\Models\Club;
use App\System\Player\Models\Player;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentRegistration;
use App\System\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeighInTest extends TestCase
{
    use RefreshDatabase;

    private function makeRegistration(CategoryStatusEnum $status = CategoryStatusEnum::RegistrationClosed): TournamentRegistration
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

        return TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => Player::factory()->create()->id,
            'player_age' => 25,
            'status' => RegistrationStatusEnum::Confirmed,
        ]);
    }

    private function url(TournamentRegistration $registration): string
    {
        $category = $registration->category;

        return "/manage/tournaments/{$category->tournament_id}/categories/{$category->id}/weigh-ins";
    }

    public function test_recording_a_weigh_in_redirects_back_to_registrations(): void
    {
        $registration = $this->makeRegistration();

        $response = $this->actingAs(User::factory()->create())
            ->post($this->url($registration), [
                'registration_id' => $registration->id,
                'weight' => 70,
            ]);

        $category = $registration->category;
        $response->assertRedirect(route('tournaments.categories.registrations.index', [
            $category->tournament_id,
            $category->id,
        ], false));
        $response->assertSessionHas('success');
    }

    public function test_weigh_in_within_bounds_is_persisted_as_made_weight(): void
    {
        $registration = $this->makeRegistration();

        $this->actingAs(User::factory()->create())
            ->post($this->url($registration), [
                'registration_id' => $registration->id,
                'weight' => 70,
            ]);

        $this->assertDatabaseHas('tournament_weigh_ins', [
            'registration_id' => $registration->id,
            'made_weight' => true,
        ]);
    }

    public function test_weigh_in_outside_bounds_is_persisted_as_failed(): void
    {
        $registration = $this->makeRegistration();

        $this->actingAs(User::factory()->create())
            ->post($this->url($registration), [
                'registration_id' => $registration->id,
                'weight' => 100,
            ]);

        $this->assertDatabaseHas('tournament_weigh_ins', [
            'registration_id' => $registration->id,
            'made_weight' => false,
        ]);
    }

    public function test_weigh_in_blocked_when_registration_still_open(): void
    {
        $registration = $this->makeRegistration(CategoryStatusEnum::RegistrationOpen);

        $response = $this->actingAs(User::factory()->create())
            ->post($this->url($registration), [
                'registration_id' => $registration->id,
                'weight' => 70,
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('tournament_weigh_ins', [
            'registration_id' => $registration->id,
        ]);
    }
}
