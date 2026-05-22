<?php

namespace Tests\Feature;

use App\Modules\Countries\Models\Country;
use App\System\Referee\Foundation\Enums\RefereeCertificationLevelEnum;
use App\System\Referee\Models\Referee;
use App\System\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefereeTest extends TestCase
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

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Jane Smith',
            'nationality_code' => 'NP',
            'certification_level' => RefereeCertificationLevelEnum::National->value,
        ], $overrides);
    }

    // ──────────────────────────────────────────────────────────────
    // Guest / auth redirect
    // ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_from_referees_index(): void
    {
        $this->get('/manage/referees')->assertRedirect('/login');
    }

    public function test_guests_are_redirected_from_referees_create_page(): void
    {
        $this->get('/manage/referees/create')->assertRedirect('/login');
    }

    public function test_guests_cannot_store_a_referee(): void
    {
        $this->post('/manage/referees', $this->validPayload())->assertRedirect('/login');
    }

    public function test_guests_cannot_update_a_referee(): void
    {
        $referee = Referee::factory()->create();
        $this->put("/manage/referees/{$referee->id}", $this->validPayload())->assertRedirect('/login');
    }

    public function test_guests_cannot_delete_a_referee(): void
    {
        $this->delete('/manage/referees/'.Str::ulid())->assertRedirect('/login');
    }

    // ──────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_referees_list(): void
    {
        $this->actingAs($this->user())->get('/manage/referees')->assertOk();
    }

    public function test_referees_list_shows_all_existing_referees(): void
    {
        Referee::factory()->count(3)->create();

        $this->actingAs($this->user())->get('/manage/referees')->assertOk();

        $this->assertDatabaseCount('referees', 3);
    }

    // ──────────────────────────────────────────────────────────────
    // Create page
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_create_referee_page(): void
    {
        $this->actingAs($this->user())->get('/manage/referees/create')->assertOk();
    }

    // ──────────────────────────────────────────────────────────────
    // Store
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_a_referee(): void
    {
        $response = $this->actingAs($this->user())
            ->post('/manage/referees', $this->validPayload());

        $response->assertRedirect('/manage/referees');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('referees', ['full_name' => 'Jane Smith']);
    }

    public function test_store_saves_certification_level(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/referees', $this->validPayload(['certification_level' => RefereeCertificationLevelEnum::International->value]));

        $this->assertDatabaseHas('referees', [
            'full_name' => 'Jane Smith',
            'certification_level' => RefereeCertificationLevelEnum::International->value,
        ]);
    }

    public function test_store_requires_full_name(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/referees', $this->validPayload(['full_name' => '']))
            ->assertSessionHasErrors('full_name');
    }

    public function test_store_rejects_invalid_country_code(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/referees', $this->validPayload(['nationality_code' => 'XX']))
            ->assertSessionHasErrors('nationality_code');
    }

    public function test_store_allows_nullable_nationality_code(): void
    {
        $response = $this->actingAs($this->user())
            ->post('/manage/referees', $this->validPayload(['nationality_code' => null]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('referees', ['full_name' => 'Jane Smith', 'nationality_code' => null]);
    }

    public function test_store_allows_nullable_certification_level(): void
    {
        $response = $this->actingAs($this->user())
            ->post('/manage/referees', $this->validPayload(['certification_level' => null]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('referees', ['full_name' => 'Jane Smith', 'certification_level' => null]);
    }

    // ──────────────────────────────────────────────────────────────
    // Edit page
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_edit_referee_page(): void
    {
        $referee = Referee::factory()->create();

        $this->actingAs($this->user())
            ->get("/manage/referees/{$referee->id}/edit")
            ->assertOk();
    }

    public function test_edit_page_returns_404_for_nonexistent_referee(): void
    {
        $this->actingAs($this->user())
            ->get('/manage/referees/'.Str::ulid().'/edit')
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_a_referee(): void
    {
        $referee = Referee::factory()->create(['full_name' => 'Old Name']);

        $response = $this->actingAs($this->user())
            ->put("/manage/referees/{$referee->id}", $this->validPayload(['full_name' => 'Updated Name']));

        $response->assertRedirect('/manage/referees');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('referees', ['id' => $referee->id, 'full_name' => 'Updated Name']);
    }

    public function test_update_requires_full_name(): void
    {
        $referee = Referee::factory()->create();

        $this->actingAs($this->user())
            ->put("/manage/referees/{$referee->id}", $this->validPayload(['full_name' => '']))
            ->assertSessionHasErrors('full_name');
    }

    public function test_update_returns_404_for_nonexistent_referee(): void
    {
        $this->actingAs($this->user())
            ->put('/manage/referees/'.Str::ulid(), $this->validPayload())
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Destroy
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_delete_a_referee(): void
    {
        $referee = Referee::factory()->create();

        $response = $this->actingAs($this->user())->delete("/manage/referees/{$referee->id}");

        $response->assertRedirect('/manage/referees');
        $this->assertSoftDeleted('referees', ['id' => $referee->id]);
    }

    public function test_deleting_a_referee_does_not_affect_other_referees(): void
    {
        $refereeA = Referee::factory()->create();
        $refereeB = Referee::factory()->create();

        $this->actingAs($this->user())->delete("/manage/referees/{$refereeA->id}");

        $this->assertSoftDeleted('referees', ['id' => $refereeA->id]);
        $this->assertDatabaseHas('referees', ['id' => $refereeB->id, 'deleted_at' => null]);
    }

    public function test_delete_returns_404_for_nonexistent_referee(): void
    {
        $this->actingAs($this->user())
            ->delete('/manage/referees/'.Str::ulid())
            ->assertNotFound();
    }
}
