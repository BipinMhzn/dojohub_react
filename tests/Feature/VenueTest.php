<?php

namespace Tests\Feature;

use App\Modules\Countries\Models\Country;
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

        $response->assertRedirect('/manage/venues');
        $response->assertSessionHasNoErrors();

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
    // Edit page
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_edit_venue_page(): void
    {
        $venue = Venue::factory()->create();

        $this->actingAs($this->user())
            ->get("/manage/venues/{$venue->id}/edit")
            ->assertOk();
    }

    public function test_edit_page_returns_404_for_nonexistent_venue(): void
    {
        $this->actingAs($this->user())
            ->get('/manage/venues/'.Str::ulid().'/edit')
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_a_venue(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->actingAs($this->user())
            ->put("/manage/venues/{$venue->id}", $this->validVenuePayload(['name' => 'Updated Hall', 'city' => 'Lalitpur']));

        $response->assertRedirect('/manage/venues');
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

    public function test_authenticated_user_can_add_a_mat_to_a_venue(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->actingAs($this->user())
            ->post("/manage/venues/{$venue->id}/mats", ['mat_number' => 1, 'display_name' => 'Tatami A']);

        $response->assertRedirect("/manage/venues/{$venue->id}/edit");
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('venue_mats', ['venue_id' => $venue->id, 'mat_number' => 1, 'display_name' => 'Tatami A']);
    }

    public function test_authenticated_user_can_update_a_mat(): void
    {
        $venue = Venue::factory()->create();
        $mat = VenueMat::factory()->create(['venue_id' => $venue->id, 'mat_number' => 1]);

        $this->actingAs($this->user())
            ->put("/manage/venues/{$venue->id}/mats/{$mat->id}", ['mat_number' => 1, 'display_name' => 'Center Mat'])
            ->assertRedirect("/manage/venues/{$venue->id}/edit");

        $this->assertDatabaseHas('venue_mats', ['id' => $mat->id, 'display_name' => 'Center Mat']);
    }

    public function test_authenticated_user_can_remove_a_mat_from_a_venue(): void
    {
        $venue = Venue::factory()->create();
        $mat = VenueMat::factory()->create(['venue_id' => $venue->id]);

        $this->actingAs($this->user())
            ->delete("/manage/venues/{$venue->id}/mats/{$mat->id}")
            ->assertRedirect("/manage/venues/{$venue->id}/edit");

        $this->assertSoftDeleted('venue_mats', ['id' => $mat->id]);
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
