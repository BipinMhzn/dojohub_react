<?php

namespace Tests\Feature;

use App\Modules\Countries\Models\Country;
use App\Modules\TournamentCategories\Models\TournamentCategory;
use App\Modules\Tournaments\Models\Tournament;
use App\Modules\Tournaments\Models\TournamentBracket;
use App\Modules\Tournaments\Models\TournamentMatch;
use App\System\User\Models\User;
use App\System\Venue\Models\Venue;
use App\System\VenueMat\Models\VenueMat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VenueTest extends TestCase
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
    private function validVenuePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kathmandu Sports Hall',
            'address_line_1' => '123 Durbar Marg',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'country_code' => 'NP',
        ], $overrides);
    }

    // ──────────────────────────────────────────────────────────────
    // Guest / auth redirect
    // ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_from_venues_index(): void
    {
        $this->get('/manage/venues')->assertRedirect('/login');
    }

    public function test_guests_are_redirected_from_venues_create_page(): void
    {
        $this->get('/manage/venues/create')->assertRedirect('/login');
    }

    public function test_guests_cannot_store_a_venue(): void
    {
        $this->post('/manage/venues', $this->validVenuePayload())->assertRedirect('/login');
    }

    public function test_guests_cannot_update_a_venue(): void
    {
        $venue = Venue::factory()->create();
        $this->put("/manage/venues/{$venue->id}", $this->validVenuePayload())->assertRedirect('/login');
    }

    public function test_guests_cannot_delete_a_venue(): void
    {
        $this->delete('/manage/venues/'.Str::ulid())->assertRedirect('/login');
    }

    // ──────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_venues_list(): void
    {
        $this->actingAs($this->user())->get('/manage/venues')->assertOk();
    }

    public function test_venues_list_shows_all_existing_venues(): void
    {
        Venue::factory()->count(3)->create();

        $this->actingAs($this->user())->get('/manage/venues')->assertOk();

        $this->assertDatabaseCount('venues', 3);
    }

    // ──────────────────────────────────────────────────────────────
    // Create page
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_create_venue_page(): void
    {
        $this->actingAs($this->user())->get('/manage/venues/create')->assertOk();
    }

    // ──────────────────────────────────────────────────────────────
    // Store
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_a_venue(): void
    {
        $response = $this->actingAs($this->user())
            ->post('/manage/venues', $this->validVenuePayload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertStringContainsString('/manage/venues/', $response->headers->get('Location'));

        $this->assertDatabaseHas('venues', ['name' => 'Kathmandu Sports Hall']);
        $this->assertDatabaseHas('addresses', ['address_line_1' => '123 Durbar Marg', 'city' => 'Kathmandu']);
    }

    public function test_created_venue_is_linked_to_its_address(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/venues', $this->validVenuePayload(['name' => 'Linked Venue', 'city' => 'Pokhara']));

        $venue = Venue::where('name', 'Linked Venue')->firstOrFail();

        $this->assertNotNull($venue->address_id);
        $this->assertSame('Pokhara', $venue->address->city);
    }

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/venues', $this->validVenuePayload(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_address_line_1(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/venues', $this->validVenuePayload(['address_line_1' => '']))
            ->assertSessionHasErrors('address_line_1');
    }

    public function test_store_requires_city(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/venues', $this->validVenuePayload(['city' => '']))
            ->assertSessionHasErrors('city');
    }

    public function test_store_requires_valid_country_code(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/venues', $this->validVenuePayload(['country_code' => 'XX']))
            ->assertSessionHasErrors('country_code');
    }

    public function test_store_rejects_missing_country_code(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/venues', $this->validVenuePayload(['country_code' => '']))
            ->assertSessionHasErrors('country_code');
    }

    // ──────────────────────────────────────────────────────────────
    // Show page
    // ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_from_venue_show_page(): void
    {
        $venue = Venue::factory()->create();
        $this->get("/manage/venues/{$venue->id}")->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_venue_show_page(): void
    {
        $venue = Venue::factory()->create();

        $this->actingAs($this->user())
            ->get("/manage/venues/{$venue->id}")
            ->assertOk();
    }

    public function test_show_page_returns_404_for_nonexistent_venue(): void
    {
        $this->actingAs($this->user())
            ->get('/manage/venues/'.Str::ulid())
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Edit page (redirects to show)
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_visiting_edit_is_redirected_to_show(): void
    {
        $venue = Venue::factory()->create();

        $this->actingAs($this->user())
            ->get("/manage/venues/{$venue->id}/edit")
            ->assertRedirect("/manage/venues/{$venue->id}");
    }

    // ──────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_a_venue(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->actingAs($this->user())
            ->put("/manage/venues/{$venue->id}", $this->validVenuePayload(['name' => 'Updated Hall', 'city' => 'Lalitpur']));

        $response->assertRedirect("/manage/venues/{$venue->id}");
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'name' => 'Updated Hall']);
        $this->assertDatabaseHas('addresses', ['id' => $venue->address_id, 'city' => 'Lalitpur']);
    }

    public function test_updating_one_venue_does_not_affect_another_venues_address(): void
    {
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();

        $this->actingAs($this->user())
            ->put("/manage/venues/{$venueA->id}", $this->validVenuePayload(['city' => 'Bhaktapur']));

        $this->assertDatabaseMissing('addresses', ['id' => $venueB->address_id, 'city' => 'Bhaktapur']);
    }

    public function test_update_requires_name(): void
    {
        $venue = Venue::factory()->create();

        $this->actingAs($this->user())
            ->put("/manage/venues/{$venue->id}", $this->validVenuePayload(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_update_returns_404_for_nonexistent_venue(): void
    {
        $this->actingAs($this->user())
            ->put('/manage/venues/'.Str::ulid(), $this->validVenuePayload())
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Destroy
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_delete_a_venue(): void
    {
        $venue = Venue::factory()->create();
        $addressId = $venue->address_id;

        $response = $this->actingAs($this->user())->delete("/manage/venues/{$venue->id}");

        $response->assertRedirect('/manage/venues');
        $this->assertSoftDeleted('venues', ['id' => $venue->id]);
        $this->assertSoftDeleted('addresses', ['id' => $addressId]);
    }

    public function test_cannot_delete_venue_assigned_to_a_tournament(): void
    {
        $venue = Venue::factory()->create();

        Tournament::create([
            'name' => 'Spring Open',
            'level' => 1,
            'status' => 0,
            'venue_id' => $venue->id,
            'start_at' => now(),
        ]);

        $response = $this->actingAs($this->user())->delete("/manage/venues/{$venue->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'deleted_at' => null]);
    }

    public function test_deleting_a_venue_does_not_soft_delete_other_venues(): void
    {
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();

        $this->actingAs($this->user())->delete("/manage/venues/{$venueA->id}");

        $this->assertSoftDeleted('venues', ['id' => $venueA->id]);
        $this->assertDatabaseHas('venues', ['id' => $venueB->id, 'deleted_at' => null]);
    }

    public function test_delete_returns_404_for_nonexistent_venue(): void
    {
        $this->actingAs($this->user())
            ->delete('/manage/venues/'.Str::ulid())
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // VenueMat CRUD
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_venue_mats_page(): void
    {
        $venue = Venue::factory()->create();

        $this->actingAs($this->user())
            ->get("/manage/venues/{$venue->id}/mats")
            ->assertOk();
    }

    public function test_guests_are_redirected_from_venue_mats_page(): void
    {
        $venue = Venue::factory()->create();
        $this->get("/manage/venues/{$venue->id}/mats")->assertRedirect('/login');
    }

    public function test_authenticated_user_can_add_a_mat_to_a_venue(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->actingAs($this->user())
            ->post("/manage/venues/{$venue->id}/mats", ['mat_number' => 1, 'display_name' => 'Tatami A']);

        $response->assertRedirect("/manage/venues/{$venue->id}/mats");
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('venue_mats', ['venue_id' => $venue->id, 'mat_number' => 1, 'display_name' => 'Tatami A']);
    }

    public function test_mat_number_must_be_unique_within_a_venue(): void
    {
        $venue = Venue::factory()->create();
        VenueMat::factory()->create(['venue_id' => $venue->id, 'mat_number' => 1]);

        $this->actingAs($this->user())
            ->post("/manage/venues/{$venue->id}/mats", ['mat_number' => 1])
            ->assertSessionHasErrors('mat_number');
    }

    public function test_duplicate_mat_number_is_allowed_in_different_venues(): void
    {
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();
        VenueMat::factory()->create(['venue_id' => $venueA->id, 'mat_number' => 1]);

        $response = $this->actingAs($this->user())
            ->post("/manage/venues/{$venueB->id}/mats", ['mat_number' => 1]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('venue_mats', ['venue_id' => $venueB->id, 'mat_number' => 1]);
    }

    public function test_authenticated_user_can_update_a_mat(): void
    {
        $venue = Venue::factory()->create();
        $mat = VenueMat::factory()->create(['venue_id' => $venue->id, 'mat_number' => 1]);

        $this->actingAs($this->user())
            ->put("/manage/venues/{$venue->id}/mats/{$mat->id}", ['mat_number' => 1, 'display_name' => 'Center Mat'])
            ->assertRedirect("/manage/venues/{$venue->id}/mats");

        $this->assertDatabaseHas('venue_mats', ['id' => $mat->id, 'display_name' => 'Center Mat']);
    }

    public function test_authenticated_user_can_remove_a_mat_from_a_venue(): void
    {
        $venue = Venue::factory()->create();
        $mat = VenueMat::factory()->create(['venue_id' => $venue->id]);

        $this->actingAs($this->user())
            ->delete("/manage/venues/{$venue->id}/mats/{$mat->id}")
            ->assertRedirect("/manage/venues/{$venue->id}/mats");

        $this->assertSoftDeleted('venue_mats', ['id' => $mat->id]);
    }

    public function test_cannot_delete_mat_assigned_to_a_tournament_match(): void
    {
        $venue = Venue::factory()->create();
        $mat = VenueMat::factory()->create(['venue_id' => $venue->id, 'mat_number' => 1]);

        $tournament = Tournament::create([
            'name' => 'Spring Open',
            'level' => 1,
            'status' => 0,
            'venue_id' => $venue->id,
            'start_at' => now(),
        ]);

        $category = TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => 0,
            'age_category' => 1,
            'min_age' => 15,
            'max_age' => 18,
            'weight_category' => 1,
            'min_weight' => 60,
            'max_weight' => 66,
            'status' => 0,
        ]);

        $bracket = TournamentBracket::create([
            'tournament_category_id' => $category->id,
            'type' => 0,
            'seeding_method' => 0,
            'status' => 0,
        ]);

        TournamentMatch::create([
            'bracket_id' => $bracket->id,
            'mat_id' => $mat->id,
            'bracket_section' => 0,
            'round_number' => 1,
            'round_name' => 'Round of 16',
            'match_number' => 1,
            'status' => 0,
        ]);

        $response = $this->actingAs($this->user())
            ->delete("/manage/venues/{$venue->id}/mats/{$mat->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('venue_mats', ['id' => $mat->id, 'deleted_at' => null]);
    }

    public function test_mat_store_requires_mat_number(): void
    {
        $venue = Venue::factory()->create();

        $this->actingAs($this->user())
            ->post("/manage/venues/{$venue->id}/mats", ['mat_number' => ''])
            ->assertSessionHasErrors('mat_number');
    }

    public function test_guests_cannot_manage_mats(): void
    {
        $venue = Venue::factory()->create();
        $this->post("/manage/venues/{$venue->id}/mats", ['mat_number' => 1])->assertRedirect('/login');
    }
}
