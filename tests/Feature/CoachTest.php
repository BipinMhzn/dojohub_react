<?php

namespace Tests\Feature;

use App\System\Coach\Models\Coach;
use App\System\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CoachTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'John Coach',
        ], $overrides);
    }

    // ──────────────────────────────────────────────────────────────
    // Guest / auth redirect
    // ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_from_coaches_index(): void
    {
        $this->get('/manage/coaches')->assertRedirect('/login');
    }

    public function test_guests_are_redirected_from_coaches_create_page(): void
    {
        $this->get('/manage/coaches/create')->assertRedirect('/login');
    }

    public function test_guests_cannot_store_a_coach(): void
    {
        $this->post('/manage/coaches', $this->validPayload())->assertRedirect('/login');
    }

    public function test_guests_cannot_update_a_coach(): void
    {
        $coach = Coach::factory()->create();
        $this->put("/manage/coaches/{$coach->id}", $this->validPayload())->assertRedirect('/login');
    }

    public function test_guests_cannot_delete_a_coach(): void
    {
        $this->delete('/manage/coaches/'.Str::ulid())->assertRedirect('/login');
    }

    // ──────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_coaches_list(): void
    {
        $this->actingAs($this->user())->get('/manage/coaches')->assertOk();
    }

    public function test_coaches_list_shows_all_existing_coaches(): void
    {
        Coach::factory()->count(3)->create();

        $this->actingAs($this->user())->get('/manage/coaches')->assertOk();

        $this->assertDatabaseCount('coaches', 3);
    }

    // ──────────────────────────────────────────────────────────────
    // Create page
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_create_coach_page(): void
    {
        $this->actingAs($this->user())->get('/manage/coaches/create')->assertOk();
    }

    // ──────────────────────────────────────────────────────────────
    // Store
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_a_coach(): void
    {
        $response = $this->actingAs($this->user())
            ->post('/manage/coaches', $this->validPayload(['full_name' => 'New Coach']));

        $response->assertRedirect('/manage/coaches');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('coaches', ['full_name' => 'New Coach']);
    }

    public function test_store_requires_full_name(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/coaches', $this->validPayload(['full_name' => '']))
            ->assertSessionHasErrors('full_name');
    }

    // ──────────────────────────────────────────────────────────────
    // Edit page
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_edit_coach_page(): void
    {
        $coach = Coach::factory()->create();

        $this->actingAs($this->user())
            ->get("/manage/coaches/{$coach->id}/edit")
            ->assertOk();
    }

    public function test_edit_page_returns_404_for_nonexistent_coach(): void
    {
        $this->actingAs($this->user())
            ->get('/manage/coaches/'.Str::ulid().'/edit')
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_a_coach(): void
    {
        $coach = Coach::factory()->create(['full_name' => 'Old Name']);

        $response = $this->actingAs($this->user())
            ->put("/manage/coaches/{$coach->id}", $this->validPayload(['full_name' => 'Updated Name']));

        $response->assertRedirect('/manage/coaches');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('coaches', ['id' => $coach->id, 'full_name' => 'Updated Name']);
    }

    public function test_update_requires_full_name(): void
    {
        $coach = Coach::factory()->create();

        $this->actingAs($this->user())
            ->put("/manage/coaches/{$coach->id}", $this->validPayload(['full_name' => '']))
            ->assertSessionHasErrors('full_name');
    }

    public function test_update_returns_404_for_nonexistent_coach(): void
    {
        $this->actingAs($this->user())
            ->put('/manage/coaches/'.Str::ulid(), $this->validPayload())
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Destroy
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_delete_a_coach(): void
    {
        $coach = Coach::factory()->create();

        $response = $this->actingAs($this->user())->delete("/manage/coaches/{$coach->id}");

        $response->assertRedirect('/manage/coaches');
        $this->assertSoftDeleted('coaches', ['id' => $coach->id]);
    }

    public function test_deleting_a_coach_does_not_affect_other_coaches(): void
    {
        $coachA = Coach::factory()->create();
        $coachB = Coach::factory()->create();

        $this->actingAs($this->user())->delete("/manage/coaches/{$coachA->id}");

        $this->assertSoftDeleted('coaches', ['id' => $coachA->id]);
        $this->assertDatabaseHas('coaches', ['id' => $coachB->id, 'deleted_at' => null]);
    }

    public function test_delete_returns_404_for_nonexistent_coach(): void
    {
        $this->actingAs($this->user())
            ->delete('/manage/coaches/'.Str::ulid())
            ->assertNotFound();
    }
}
