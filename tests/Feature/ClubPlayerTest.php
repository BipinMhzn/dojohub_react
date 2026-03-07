<?php

namespace Tests\Feature;

use App\System\Club\Models\Club;
use App\System\ClubPlayer\Foundation\Enums\BeltRankEnum;
use App\System\ClubPlayer\Models\ClubPlayer;
use App\System\Player\Models\Player;
use App\System\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClubPlayerTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createClub(): Club
    {
        return Club::factory()->create();
    }

    private function createPlayer(): Player
    {
        return Player::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function validAssignPayload(array $overrides = []): array
    {
        return array_merge([
            'player_id' => Player::factory()->create()->id,
            'started_at' => now()->subMonth()->format('Y-m-d'),
        ], $overrides);
    }

    // ──────────────────────────────────────────────────────────────
    // Guest / auth redirect
    // ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_from_club_show(): void
    {
        $club = $this->createClub();

        $this->get("/manage/clubs/{$club->id}")->assertRedirect('/login');
    }

    public function test_guests_cannot_assign_a_player(): void
    {
        $club = $this->createClub();

        $this->post("/manage/clubs/{$club->id}/players", $this->validAssignPayload())
            ->assertRedirect('/login');
    }

    public function test_guests_cannot_end_a_membership(): void
    {
        $membership = ClubPlayer::factory()->create();

        $this->put("/manage/clubs/{$membership->club_id}/players/{$membership->id}", [
            'ended_at' => now()->format('Y-m-d'),
        ])->assertRedirect('/login');
    }

    // ──────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_club_show(): void
    {
        $club = $this->createClub();

        $this->actingAs($this->createUser())
            ->get("/manage/clubs/{$club->id}")
            ->assertOk();
    }

    public function test_club_show_returns_404_for_nonexistent_club(): void
    {
        $this->actingAs($this->createUser())
            ->get('/manage/clubs/'.Str::ulid())
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Store — assign player
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_assign_a_player_to_a_club(): void
    {
        $club = $this->createClub();
        $player = $this->createPlayer();
        $date = now()->subMonth()->format('Y-m-d');

        $response = $this->actingAs($this->createUser())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
                'started_at' => $date,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clubs_players', [
            'club_id' => $club->id,
            'player_id' => $player->id,
            'ended_at' => null,
        ]);
    }

    public function test_can_assign_player_with_belt_rank_and_grade(): void
    {
        $club = $this->createClub();
        $player = $this->createPlayer();

        $this->actingAs($this->createUser())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
                'started_at' => now()->subMonth()->format('Y-m-d'),
                'belt_rank' => BeltRankEnum::BLACK->value,
                'grade' => '2nd dan',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clubs_players', [
            'club_id' => $club->id,
            'player_id' => $player->id,
            'belt_rank' => BeltRankEnum::BLACK->value,
            'grade' => '2nd dan',
        ]);
    }

    public function test_cannot_assign_player_who_already_has_active_membership(): void
    {
        $club = $this->createClub();
        $player = $this->createPlayer();

        ClubPlayer::factory()->create([
            'club_id' => $club->id,
            'player_id' => $player->id,
            'ended_at' => null,
        ]);

        $this->actingAs($this->createUser())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
                'started_at' => now()->subWeek()->format('Y-m-d'),
            ])
            ->assertStatus(500);
    }

    public function test_store_requires_player_id(): void
    {
        $club = $this->createClub();

        $this->actingAs($this->createUser())
            ->post("/manage/clubs/{$club->id}/players", [
                'started_at' => now()->subMonth()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('player_id');
    }

    public function test_store_requires_started_at(): void
    {
        $club = $this->createClub();
        $player = $this->createPlayer();

        $this->actingAs($this->createUser())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
            ])
            ->assertSessionHasErrors('started_at');
    }

    public function test_store_rejects_future_started_at(): void
    {
        $club = $this->createClub();
        $player = $this->createPlayer();

        $this->actingAs($this->createUser())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
                'started_at' => now()->addDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('started_at');
    }

    public function test_store_rejects_invalid_belt_rank(): void
    {
        $club = $this->createClub();
        $player = $this->createPlayer();

        $this->actingAs($this->createUser())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
                'started_at' => now()->subMonth()->format('Y-m-d'),
                'belt_rank' => 99,
            ])
            ->assertSessionHasErrors('belt_rank');
    }

    public function test_store_rejects_grade_exceeding_max_length(): void
    {
        $club = $this->createClub();
        $player = $this->createPlayer();

        $this->actingAs($this->createUser())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
                'started_at' => now()->subMonth()->format('Y-m-d'),
                'grade' => str_repeat('a', 51),
            ])
            ->assertSessionHasErrors('grade');
    }

    // ──────────────────────────────────────────────────────────────
    // Update — end membership
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_end_a_membership(): void
    {
        $club = $this->createClub();
        $player = $this->createPlayer();
        $startDate = now()->subMonths(3);

        $membership = ClubPlayer::factory()->create([
            'club_id' => $club->id,
            'player_id' => $player->id,
            'started_at' => $startDate,
            'ended_at' => null,
        ]);

        $endDate = now()->subMonth()->format('Y-m-d');

        $response = $this->actingAs($this->createUser())
            ->put("/manage/clubs/{$club->id}/players/{$membership->id}", [
                'ended_at' => $endDate,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clubs_players', [
            'id' => $membership->id,
            'ended_at' => $endDate.' 00:00:00',
        ]);
    }

    public function test_update_requires_ended_at(): void
    {
        $membership = ClubPlayer::factory()->create(['ended_at' => null]);

        $this->actingAs($this->createUser())
            ->put("/manage/clubs/{$membership->club_id}/players/{$membership->id}", [])
            ->assertSessionHasErrors('ended_at');
    }

    public function test_update_rejects_ended_at_before_started_at(): void
    {
        $startDate = now()->subMonths(2);

        $membership = ClubPlayer::factory()->create([
            'started_at' => $startDate,
            'ended_at' => null,
        ]);

        $this->actingAs($this->createUser())
            ->put("/manage/clubs/{$membership->club_id}/players/{$membership->id}", [
                'ended_at' => $startDate->copy()->subDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('ended_at');
    }

    public function test_update_returns_404_for_nonexistent_membership(): void
    {
        $club = $this->createClub();

        $this->actingAs($this->createUser())
            ->put("/manage/clubs/{$club->id}/players/".Str::ulid(), [
                'ended_at' => now()->format('Y-m-d'),
            ])
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Re-join: same player can be assigned again after membership ends
    // ──────────────────────────────────────────────────────────────

    public function test_player_can_rejoin_same_club_after_membership_ends(): void
    {
        $club = $this->createClub();
        $player = $this->createPlayer();

        ClubPlayer::factory()->create([
            'club_id' => $club->id,
            'player_id' => $player->id,
            'started_at' => now()->subYears(2),
            'ended_at' => now()->subYear(),
        ]);

        $this->actingAs($this->createUser())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
                'started_at' => now()->subMonth()->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('clubs_players', 2);
    }
}
