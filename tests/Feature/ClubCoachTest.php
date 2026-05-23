<?php

namespace Tests\Feature;

use App\System\Club\Models\Club;
use App\System\ClubCoach\Models\ClubCoach;
use App\System\Coach\Models\Coach;
use App\System\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClubCoachTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    // ──────────────────────────────────────────────────────────────
    // Guest / auth redirect
    // ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_from_index(): void
    {
        $club = Club::factory()->create();
        $this->get("/manage/clubs/{$club->id}/coaches")->assertRedirect('/login');
    }

    public function test_guests_cannot_assign_a_coach(): void
    {
        $club = Club::factory()->create();
        $coach = Coach::factory()->create();

        $this->post("/manage/clubs/{$club->id}/coaches", [
            'coach_id' => $coach->id,
            'started_at' => now()->format('Y-m-d'),
        ])->assertRedirect('/login');
    }

    // ──────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_club_coaches_index(): void
    {
        $club = Club::factory()->create();

        $this->actingAs($this->user())
            ->get("/manage/clubs/{$club->id}/coaches")
            ->assertOk();
    }

    public function test_index_lists_active_memberships_by_default(): void
    {
        $club = Club::factory()->create();
        ClubCoach::factory()->count(2)->create(['club_id' => $club->id]);
        ClubCoach::factory()->ended()->create(['club_id' => $club->id]);

        $this->actingAs($this->user())
            ->get("/manage/clubs/{$club->id}/coaches")
            ->assertOk();

        $this->assertDatabaseCount('clubs_coaches', 3);
    }

    // ──────────────────────────────────────────────────────────────
    // Store (assign coach)
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_assign_a_coach(): void
    {
        $club = Club::factory()->create();
        $coach = Coach::factory()->create();

        $response = $this->actingAs($this->user())
            ->post("/manage/clubs/{$club->id}/coaches", [
                'coach_id' => $coach->id,
                'started_at' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect("/manage/clubs/{$club->id}/coaches");
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clubs_coaches', [
            'club_id' => $club->id,
            'coach_id' => $coach->id,
            'ended_at' => null,
        ]);
    }

    public function test_store_rejects_duplicate_active_membership(): void
    {
        $club = Club::factory()->create();
        $coach = Coach::factory()->create();
        ClubCoach::factory()->create(['club_id' => $club->id, 'coach_id' => $coach->id]);

        $this->actingAs($this->user())
            ->post("/manage/clubs/{$club->id}/coaches", [
                'coach_id' => $coach->id,
                'started_at' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('coach_id');
    }

    public function test_store_allows_reassignment_after_membership_ended(): void
    {
        $club = Club::factory()->create();
        $coach = Coach::factory()->create();
        ClubCoach::factory()->ended()->create(['club_id' => $club->id, 'coach_id' => $coach->id]);

        $response = $this->actingAs($this->user())
            ->post("/manage/clubs/{$club->id}/coaches", [
                'coach_id' => $coach->id,
                'started_at' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('clubs_coaches', 2);
    }

    public function test_store_requires_coach_id(): void
    {
        $club = Club::factory()->create();

        $this->actingAs($this->user())
            ->post("/manage/clubs/{$club->id}/coaches", ['started_at' => now()->format('Y-m-d')])
            ->assertSessionHasErrors('coach_id');
    }

    public function test_store_rejects_future_started_at(): void
    {
        $club = Club::factory()->create();
        $coach = Coach::factory()->create();

        $this->actingAs($this->user())
            ->post("/manage/clubs/{$club->id}/coaches", [
                'coach_id' => $coach->id,
                'started_at' => now()->addDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('started_at');
    }

    public function test_store_rejects_nonexistent_coach(): void
    {
        $club = Club::factory()->create();

        $this->actingAs($this->user())
            ->post("/manage/clubs/{$club->id}/coaches", [
                'coach_id' => (string) Str::ulid(),
                'started_at' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('coach_id');
    }

    // ──────────────────────────────────────────────────────────────
    // Update (end membership)
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_end_a_membership(): void
    {
        $club = Club::factory()->create();
        $membership = ClubCoach::factory()->create([
            'club_id' => $club->id,
            'started_at' => now()->subYear(),
        ]);

        $response = $this->actingAs($this->user())
            ->put("/manage/clubs/{$club->id}/coaches/{$membership->id}", [
                'ended_at' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect("/manage/clubs/{$club->id}/coaches");
        $response->assertSessionHasNoErrors();
        $this->assertNotNull($membership->fresh()->ended_at);
    }

    public function test_update_rejects_ended_at_before_started_at(): void
    {
        $club = Club::factory()->create();
        $membership = ClubCoach::factory()->create([
            'club_id' => $club->id,
            'started_at' => now(),
        ]);

        $this->actingAs($this->user())
            ->put("/manage/clubs/{$club->id}/coaches/{$membership->id}", [
                'ended_at' => now()->subDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('ended_at');
    }
}
