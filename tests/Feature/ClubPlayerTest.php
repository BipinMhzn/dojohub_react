<?php

namespace Tests\Feature;

use App\Modules\BeltRanks\Models\BeltRank;
use App\Modules\Countries\Models\Country;
use App\System\Club\Models\Club;
use App\System\ClubPlayer\Models\ClubPlayer;
use App\System\Player\Models\Player;
use App\System\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClubPlayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal']);
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    private function club(): Club
    {
        return Club::factory()->create();
    }

    private function player(): Player
    {
        return Player::factory()->create();
    }

    private function beltRank(): BeltRank
    {
        return BeltRank::firstOrCreate(
            ['code' => '1D'],
            ['name' => 'Shodan', 'kind' => 1, 'color' => 'Black', 'sort_order' => 7]
        );
    }

    /** @return array<string, mixed> */
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
        $this->get("/manage/clubs/{$this->club()->id}")->assertRedirect('/login');
    }

    public function test_guests_cannot_assign_a_player(): void
    {
        $this->post("/manage/clubs/{$this->club()->id}/players", $this->validAssignPayload())
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
    // Show
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_club_show(): void
    {
        $this->actingAs($this->user())->get("/manage/clubs/{$this->club()->id}")->assertOk();
    }

    public function test_club_show_returns_404_for_nonexistent_club(): void
    {
        $this->actingAs($this->user())->get('/manage/clubs/'.Str::ulid())->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Store — assign player
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_assign_a_player_to_a_club(): void
    {
        $club = $this->club();
        $player = $this->player();
        $date = now()->subMonth()->format('Y-m-d');

        $response = $this->actingAs($this->user())
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

    public function test_can_assign_player_with_belt_rank_and_grade_certificate(): void
    {
        $club = $this->club();
        $player = $this->player();
        $beltRank = $this->beltRank();

        $this->actingAs($this->user())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
                'started_at' => now()->subMonth()->format('Y-m-d'),
                'belt_rank_id' => $beltRank->id,
                'grade_certificate' => 'Shodan Certificate',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clubs_players', [
            'club_id' => $club->id,
            'player_id' => $player->id,
            'belt_rank_id' => $beltRank->id,
            'grade_certificate' => 'Shodan Certificate',
        ]);
    }

    public function test_cannot_assign_player_who_already_has_active_membership(): void
    {
        $club = $this->club();
        $player = $this->player();

        ClubPlayer::factory()->create([
            'club_id' => $club->id,
            'player_id' => $player->id,
            'ended_at' => null,
        ]);

        $this->actingAs($this->user())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
                'started_at' => now()->subWeek()->format('Y-m-d'),
            ])
            ->assertStatus(500);
    }

    public function test_store_requires_player_id(): void
    {
        $this->actingAs($this->user())
            ->post("/manage/clubs/{$this->club()->id}/players", [
                'started_at' => now()->subMonth()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('player_id');
    }

    public function test_store_requires_started_at(): void
    {
        $this->actingAs($this->user())
            ->post("/manage/clubs/{$this->club()->id}/players", [
                'player_id' => $this->player()->id,
            ])
            ->assertSessionHasErrors('started_at');
    }

    public function test_store_rejects_future_started_at(): void
    {
        $this->actingAs($this->user())
            ->post("/manage/clubs/{$this->club()->id}/players", [
                'player_id' => $this->player()->id,
                'started_at' => now()->addDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('started_at');
    }

    public function test_store_rejects_invalid_belt_rank_id(): void
    {
        $this->actingAs($this->user())
            ->post("/manage/clubs/{$this->club()->id}/players", [
                'player_id' => $this->player()->id,
                'started_at' => now()->subMonth()->format('Y-m-d'),
                'belt_rank_id' => 'non-existent-id',
            ])
            ->assertSessionHasErrors('belt_rank_id');
    }

    public function test_store_rejects_grade_certificate_exceeding_max_length(): void
    {
        $this->actingAs($this->user())
            ->post("/manage/clubs/{$this->club()->id}/players", [
                'player_id' => $this->player()->id,
                'started_at' => now()->subMonth()->format('Y-m-d'),
                'grade_certificate' => str_repeat('a', 101),
            ])
            ->assertSessionHasErrors('grade_certificate');
    }

    // ──────────────────────────────────────────────────────────────
    // Update — end membership
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_end_a_membership(): void
    {
        $club = $this->club();
        $player = $this->player();
        $startDate = now()->subMonths(3);

        $membership = ClubPlayer::factory()->create([
            'club_id' => $club->id,
            'player_id' => $player->id,
            'started_at' => $startDate,
            'ended_at' => null,
        ]);

        $endDate = now()->subMonth()->format('Y-m-d');

        $response = $this->actingAs($this->user())
            ->put("/manage/clubs/{$club->id}/players/{$membership->id}", ['ended_at' => $endDate]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clubs_players', ['id' => $membership->id, 'ended_at' => $endDate.' 00:00:00']);
    }

    public function test_update_requires_ended_at(): void
    {
        $membership = ClubPlayer::factory()->create(['ended_at' => null]);

        $this->actingAs($this->user())
            ->put("/manage/clubs/{$membership->club_id}/players/{$membership->id}", [])
            ->assertSessionHasErrors('ended_at');
    }

    public function test_update_rejects_ended_at_before_started_at(): void
    {
        $startDate = now()->subMonths(2);

        $membership = ClubPlayer::factory()->create(['started_at' => $startDate, 'ended_at' => null]);

        $this->actingAs($this->user())
            ->put("/manage/clubs/{$membership->club_id}/players/{$membership->id}", [
                'ended_at' => $startDate->copy()->subDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('ended_at');
    }

    public function test_update_returns_404_for_nonexistent_membership(): void
    {
        $this->actingAs($this->user())
            ->put("/manage/clubs/{$this->club()->id}/players/".Str::ulid(), [
                'ended_at' => now()->format('Y-m-d'),
            ])
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Re-join
    // ──────────────────────────────────────────────────────────────

    public function test_player_can_rejoin_same_club_after_membership_ends(): void
    {
        $club = $this->club();
        $player = $this->player();

        ClubPlayer::factory()->create([
            'club_id' => $club->id,
            'player_id' => $player->id,
            'started_at' => now()->subYears(2),
            'ended_at' => now()->subYear(),
        ]);

        $this->actingAs($this->user())
            ->post("/manage/clubs/{$club->id}/players", [
                'player_id' => $player->id,
                'started_at' => now()->subMonth()->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('clubs_players', 2);
    }
}
