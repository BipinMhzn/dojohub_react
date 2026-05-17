<?php

namespace Tests\Feature;

use App\System\User\Models\User;
use App\System\Venue\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VenueTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    private function createUser(): User
    {
        return User::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function validVenuePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kathmandu Sports Hall',
            'mat_count' => 4,
            'address' => '123 Durbar Marg',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'country' => 'Nepal',
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
        $venue = Venue::factory()->create();

        $this->delete("/manage/venues/{$venue->id}")->assertRedirect('/login');
    }

    // ──────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_venues_list(): void
    {
        $this->actingAs($this->createUser())
            ->get('/manage/venues')
            ->assertOk();
    }

    public function test_venues_list_shows_all_existing_venues(): void
    {
        Venue::factory()->count(3)->create();

        $this->actingAs($this->createUser())
            ->get('/manage/venues')
            ->assertOk();

        $this->assertDatabaseCount('venues', 3);
    }

    // ──────────────────────────────────────────────────────────────
    // Create page
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_create_venue_page(): void
    {
        $this->actingAs($this->createUser())
            ->get('/manage/venues/create')
            ->assertOk();
    }

    // ──────────────────────────────────────────────────────────────
    // Store
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_a_venue(): void
    {
        $response = $this->actingAs($this->createUser())
            ->post('/manage/venues', $this->validVenuePayload());

        $response->assertRedirect('/manage/venues');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('venues', [
            'name' => 'Kathmandu Sports Hall',
            'mat_count' => 4,
        ]);

        $this->assertDatabaseHas('addresses', [
            'address' => '123 Durbar Marg',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'country' => 'Nepal',
        ]);
    }

    public function test_created_venue_is_linked_to_its_address(): void
    {
        $this->actingAs($this->createUser())
            ->post('/manage/venues', $this->validVenuePayload([
                'name' => 'Linked Venue',
                'city' => 'Pokhara',
            ]));

        $venue = Venue::where('name', 'Linked Venue')->firstOrFail();

        $this->assertNotNull($venue->address_id);
        $this->assertNotNull($venue->address);
        $this->assertSame('Pokhara', $venue->address->city);
    }

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->createUser())
            ->post('/manage/venues', $this->validVenuePayload(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_mat_count(): void
    {
        $this->actingAs($this->createUser())
            ->post('/manage/venues', $this->validVenuePayload(['mat_count' => '']))
            ->assertSessionHasErrors('mat_count');
    }

    public function test_store_rejects_mat_count_below_minimum(): void
    {
        $this->actingAs($this->createUser())
            ->post('/manage/venues', $this->validVenuePayload(['mat_count' => 0]))
            ->assertSessionHasErrors('mat_count');
    }

    public function test_store_rejects_mat_count_above_maximum(): void
    {
        $this->actingAs($this->createUser())
            ->post('/manage/venues', $this->validVenuePayload(['mat_count' => 100]))
            ->assertSessionHasErrors('mat_count');
    }

    public function test_store_requires_address(): void
    {
        $this->actingAs($this->createUser())
            ->post('/manage/venues', $this->validVenuePayload(['address' => '']))
            ->assertSessionHasErrors('address');
    }

    public function test_store_requires_city(): void
    {
        $this->actingAs($this->createUser())
            ->post('/manage/venues', $this->validVenuePayload(['city' => '']))
            ->assertSessionHasErrors('city');
    }

    public function test_store_requires_state(): void
    {
        $this->actingAs($this->createUser())
            ->post('/manage/venues', $this->validVenuePayload(['state' => '']))
            ->assertSessionHasErrors('state');
    }

    public function test_store_requires_country(): void
    {
        $this->actingAs($this->createUser())
            ->post('/manage/venues', $this->validVenuePayload(['country' => '']))
            ->assertSessionHasErrors('country');
    }

    // ──────────────────────────────────────────────────────────────
    // Edit page
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_edit_venue_page(): void
    {
        $venue = Venue::factory()->create();

        $this->actingAs($this->createUser())
            ->get("/manage/venues/{$venue->id}/edit")
            ->assertOk();
    }

    public function test_edit_page_returns_404_for_nonexistent_venue(): void
    {
        $this->actingAs($this->createUser())
            ->get('/manage/venues/'.Str::ulid().'/edit')
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_a_venue(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->actingAs($this->createUser())
            ->put("/manage/venues/{$venue->id}", $this->validVenuePayload([
                'name' => 'Updated Hall',
                'mat_count' => 8,
                'city' => 'Lalitpur',
            ]));

        $response->assertRedirect('/manage/venues');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Updated Hall',
            'mat_count' => 8,
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $venue->address_id,
            'city' => 'Lalitpur',
        ]);
    }

    public function test_updating_one_venue_does_not_affect_another_venues_address(): void
    {
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();

        $this->actingAs($this->createUser())
            ->put("/manage/venues/{$venueA->id}", $this->validVenuePayload(['city' => 'Bhaktapur']));

        $this->assertDatabaseMissing('addresses', [
            'id' => $venueB->address_id,
            'city' => 'Bhaktapur',
        ]);
    }

    public function test_update_requires_name(): void
    {
        $venue = Venue::factory()->create();

        $this->actingAs($this->createUser())
            ->put("/manage/venues/{$venue->id}", $this->validVenuePayload(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_update_rejects_invalid_mat_count(): void
    {
        $venue = Venue::factory()->create();

        $this->actingAs($this->createUser())
            ->put("/manage/venues/{$venue->id}", $this->validVenuePayload(['mat_count' => 0]))
            ->assertSessionHasErrors('mat_count');
    }

    public function test_update_returns_404_for_nonexistent_venue(): void
    {
        $this->actingAs($this->createUser())
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

        $response = $this->actingAs($this->createUser())
            ->delete("/manage/venues/{$venue->id}");

        $response->assertRedirect('/manage/venues');
        $response->assertSessionHasNoErrors();

        $this->assertSoftDeleted('venues', ['id' => $venue->id]);
        $this->assertSoftDeleted('addresses', ['id' => $addressId]);
    }

    public function test_deleting_a_venue_does_not_soft_delete_other_venues(): void
    {
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();

        $this->actingAs($this->createUser())
            ->delete("/manage/venues/{$venueA->id}");

        $this->assertSoftDeleted('venues', ['id' => $venueA->id]);
        $this->assertDatabaseHas('venues', ['id' => $venueB->id, 'deleted_at' => null]);
    }

    public function test_delete_returns_404_for_nonexistent_venue(): void
    {
        $this->actingAs($this->createUser())
            ->delete('/manage/venues/'.Str::ulid())
            ->assertNotFound();
    }
}
