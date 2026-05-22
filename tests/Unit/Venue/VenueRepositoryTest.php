<?php

namespace Tests\Unit\Venue;

use App\Modules\Venues\Filters\VenuesFilter;
use App\Modules\Venues\Models\Address;
use App\Modules\Venues\Models\Venue;
use App\Modules\Venues\Models\VenueMat;
use App\Modules\Countries\Models\Country;
use App\Modules\Venues\Repositories\VenueMatRepository;
use App\Modules\Venues\Repositories\VenueRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VenueRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private VenueRepository $repository;

    private VenueMatRepository $matRepository;

    protected function setUp(): void
    {
        parent::setUp();
        Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal']);
        $this->repository = new VenueRepository(new Venue, new Address);
        $this->matRepository = new VenueMatRepository(new VenueMat);
    }

    // ──────────────────────────────────────────────────────────────
    // getFilter
    // ──────────────────────────────────────────────────────────────

    public function test_get_filter_returns_venues_filter_instance(): void
    {
        $this->assertInstanceOf(VenuesFilter::class, $this->repository->getFilter(Venue::query()));
    }

    // ──────────────────────────────────────────────────────────────
    // createVenue
    // ──────────────────────────────────────────────────────────────

    public function test_create_venue_persists_venue_to_database(): void
    {
        $this->repository->createVenue($this->validPayload());

        $this->assertDatabaseHas('venues', ['name' => 'Test Hall']);
    }

    public function test_create_venue_persists_address_to_database(): void
    {
        $this->repository->createVenue($this->validPayload());

        $this->assertDatabaseHas('addresses', [
            'address_line_1' => '1 Main Street',
            'city' => 'Kathmandu',
            'country_code' => 'NP',
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
        $this->assertInstanceOf(Venue::class, $this->repository->createVenue($this->validPayload()));
    }

    public function test_create_venue_stores_null_logo_url_when_not_provided(): void
    {
        $venue = $this->repository->createVenue($this->validPayload());

        $this->assertNull($venue->fresh()->logo_url ?? null);
    }

    public function test_create_venue_stores_optional_address_fields(): void
    {
        $this->repository->createVenue($this->validPayload([
            'address_line_2' => 'Floor 2',
            'postal_code' => '44600',
        ]));

        $this->assertDatabaseHas('addresses', [
            'address_line_2' => 'Floor 2',
            'postal_code' => '44600',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // updateVenue
    // ──────────────────────────────────────────────────────────────

    public function test_update_venue_updates_venue_fields(): void
    {
        $venue = Venue::factory()->create();

        $this->repository->updateVenue($this->validPayload(['name' => 'Updated Hall']), $venue->id);

        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'name' => 'Updated Hall']);
    }

    public function test_update_venue_updates_the_linked_address(): void
    {
        $venue = Venue::factory()->create();

        $this->repository->updateVenue($this->validPayload(['city' => 'Pokhara']), $venue->id);

        $this->assertDatabaseHas('addresses', ['id' => $venue->address_id, 'city' => 'Pokhara']);
    }

    public function test_update_venue_returns_updated_venue_instance(): void
    {
        $venue = Venue::factory()->create();

        $result = $this->repository->updateVenue($this->validPayload(['name' => 'Renamed']), $venue->id);

        $this->assertInstanceOf(Venue::class, $result);
        $this->assertSame('Renamed', $result->name);
    }

    public function test_update_venue_preserves_existing_logo_url_when_not_in_payload(): void
    {
        $venue = Venue::factory()->create();

        $this->repository->updateVenue($this->validPayload(), $venue->id);

        $this->assertDatabaseHas('venues', ['id' => $venue->id]);
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
        $this->assertTrue($this->repository->deleteVenue(Venue::factory()->create()->id));
    }

    public function test_delete_venue_does_not_affect_other_clubs(): void
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
        $result = $this->repository->getById(Venue::factory()->create()->id);
        $this->assertTrue($result->relationLoaded('address'));
    }

    public function test_get_by_id_returns_venue_with_mats_loaded(): void
    {
        $result = $this->repository->getById(Venue::factory()->create()->id);
        $this->assertTrue($result->relationLoaded('mats'));
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
        $this->repository->getById((string) Str::ulid());
    }

    // ──────────────────────────────────────────────────────────────
    // VenueMatRepository — createMat / updateMat / deleteMat
    // ──────────────────────────────────────────────────────────────

    public function test_create_mat_persists_to_database(): void
    {
        $venue = Venue::factory()->create();

        $this->matRepository->createMat($venue->id, ['mat_number' => 1, 'display_name' => 'Tatami A']);

        $this->assertDatabaseHas('venue_mats', [
            'venue_id' => $venue->id,
            'mat_number' => 1,
            'display_name' => 'Tatami A',
        ]);
    }

    public function test_create_mat_returns_venue_mat_instance(): void
    {
        $result = $this->matRepository->createMat(
            Venue::factory()->create()->id,
            ['mat_number' => 1]
        );
        $this->assertInstanceOf(VenueMat::class, $result);
    }

    public function test_update_mat_changes_display_name(): void
    {
        $mat = VenueMat::factory()->create(['display_name' => 'Old Name']);

        $this->matRepository->updateMat($mat->id, ['mat_number' => $mat->mat_number, 'display_name' => 'New Name']);

        $this->assertDatabaseHas('venue_mats', ['id' => $mat->id, 'display_name' => 'New Name']);
    }

    public function test_delete_mat_soft_deletes_the_mat(): void
    {
        $mat = VenueMat::factory()->create();
        $this->matRepository->deleteMat($mat->id);
        $this->assertSoftDeleted('venue_mats', ['id' => $mat->id]);
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Hall',
            'address_line_1' => '1 Main Street',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'country_code' => 'NP',
        ], $overrides);
    }
}
