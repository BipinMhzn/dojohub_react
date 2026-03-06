<?php

namespace Tests\Unit\Venue;

use App\Modules\Venues\Filters\VenuesFilter;
use App\Modules\Venues\Models\Address;
use App\Modules\Venues\Models\Venue;
use App\Modules\Venues\Repositories\VenueRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private VenueRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new VenueRepository(new Venue(), new Address());
    }

    // ──────────────────────────────────────────────────────────────
    // getFilter
    // ──────────────────────────────────────────────────────────────

    public function test_get_filter_returns_venues_filter_instance(): void
    {
        $builder = Venue::query();

        $this->assertInstanceOf(VenuesFilter::class, $this->repository->getFilter($builder));
    }

    // ──────────────────────────────────────────────────────────────
    // createVenue
    // ──────────────────────────────────────────────────────────────

    public function test_create_venue_persists_venue_to_database(): void
    {
        $this->repository->createVenue($this->validPayload());

        $this->assertDatabaseHas('venues', ['name' => 'Test Hall', 'mat_count' => 3]);
    }

    public function test_create_venue_persists_address_to_database(): void
    {
        $this->repository->createVenue($this->validPayload());

        $this->assertDatabaseHas('addresses', [
            'address' => '1 Main Street',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'country' => 'Nepal',
        ]);
    }

    public function test_create_venue_links_venue_to_address(): void
    {
        $venue = $this->repository->createVenue($this->validPayload());

        $this->assertNotNull($venue->address_id);
        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'address_id' => $venue->address_id]);
    }

    public function test_create_venue_returns_venue_instance(): void
    {
        $result = $this->repository->createVenue($this->validPayload());

        $this->assertInstanceOf(Venue::class, $result);
    }

    // ──────────────────────────────────────────────────────────────
    // updateVenue
    // ──────────────────────────────────────────────────────────────

    public function test_update_venue_updates_venue_fields(): void
    {
        $venue = Venue::factory()->create();

        $this->repository->updateVenue(
            $this->validPayload(['name' => 'Updated Hall', 'mat_count' => 6]),
            $venue->id
        );

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Updated Hall',
            'mat_count' => 6,
        ]);
    }

    public function test_update_venue_updates_the_linked_address(): void
    {
        $venue = Venue::factory()->create();

        $this->repository->updateVenue(
            $this->validPayload(['city' => 'Pokhara']),
            $venue->id
        );

        $this->assertDatabaseHas('addresses', [
            'id' => $venue->address_id,
            'city' => 'Pokhara',
        ]);
    }

    public function test_update_venue_returns_updated_venue_instance(): void
    {
        $venue = Venue::factory()->create();

        $result = $this->repository->updateVenue(
            $this->validPayload(['name' => 'Renamed']),
            $venue->id
        );

        $this->assertInstanceOf(Venue::class, $result);
        $this->assertSame('Renamed', $result->name);
    }

    // ──────────────────────────────────────────────────────────────
    // deleteVenue
    // ──────────────────────────────────────────────────────────────

    public function test_delete_venue_soft_deletes_the_venue(): void
    {
        $venue = Venue::factory()->create();

        $this->repository->deleteVenue($venue->id);

        $this->assertSoftDeleted('venues', ['id' => $venue->id]);
    }

    public function test_delete_venue_soft_deletes_the_linked_address(): void
    {
        $venue = Venue::factory()->create();
        $addressId = $venue->address_id;

        $this->repository->deleteVenue($venue->id);

        $this->assertSoftDeleted('addresses', ['id' => $addressId]);
    }

    public function test_delete_venue_returns_true(): void
    {
        $venue = Venue::factory()->create();

        $this->assertTrue($this->repository->deleteVenue($venue->id));
    }

    public function test_delete_venue_does_not_affect_other_venues(): void
    {
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();

        $this->repository->deleteVenue($venueA->id);

        $this->assertDatabaseHas('venues', ['id' => $venueB->id, 'deleted_at' => null]);
    }

    // ──────────────────────────────────────────────────────────────
    // getById
    // ──────────────────────────────────────────────────────────────

    public function test_get_by_id_returns_venue_with_address_loaded(): void
    {
        $venue = Venue::factory()->create();

        $result = $this->repository->getById($venue->id);

        $this->assertInstanceOf(Venue::class, $result);
        $this->assertTrue($result->relationLoaded('address'));
    }

    public function test_get_by_id_returns_correct_venue(): void
    {
        $venue = Venue::factory()->create(['name' => 'Specific Venue']);

        $result = $this->repository->getById($venue->id);

        $this->assertSame($venue->id, $result->id);
        $this->assertSame('Specific Venue', $result->name);
    }

    public function test_get_by_id_throws_for_nonexistent_id(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->getById((string) \Illuminate\Support\Str::ulid());
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Hall',
            'mat_count' => 3,
            'address' => '1 Main Street',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'country' => 'Nepal',
        ], $overrides);
    }
}
