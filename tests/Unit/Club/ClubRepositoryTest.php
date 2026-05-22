<?php

namespace Tests\Unit\Club;

use App\Modules\Clubs\Filters\ClubsFilter;
use App\Modules\Clubs\Models\Address;
use App\Modules\Clubs\Models\Club;
use App\Modules\Clubs\Repositories\ClubRepository;
use App\Modules\Countries\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClubRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ClubRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal']);
        $this->repository = new ClubRepository(new Club, new Address);
    }

    // ──────────────────────────────────────────────────────────────
    // getFilter
    // ──────────────────────────────────────────────────────────────

    public function test_get_filter_returns_clubs_filter_instance(): void
    {
        $this->assertInstanceOf(ClubsFilter::class, $this->repository->getFilter(Club::query()));
    }

    // ──────────────────────────────────────────────────────────────
    // createClub (with address)
    // ──────────────────────────────────────────────────────────────

    public function test_create_club_persists_club_to_database(): void
    {
        $this->repository->createClub($this->validPayload());

        $this->assertDatabaseHas('clubs', ['name' => 'Test Club', 'abbreviation' => 'TC']);
    }

    public function test_create_club_persists_address_to_database(): void
    {
        $this->repository->createClub($this->validPayload());

        $this->assertDatabaseHas('addresses', [
            'address_line_1' => '1 Main Street',
            'city' => 'Kathmandu',
            'country_code' => 'NP',
        ]);
    }

    public function test_create_club_links_club_to_address(): void
    {
        $club = $this->repository->createClub($this->validPayload());

        $this->assertNotNull($club->address_id);
        $this->assertDatabaseHas('clubs', ['id' => $club->id, 'address_id' => $club->address_id]);
    }

    public function test_create_club_returns_club_instance(): void
    {
        $this->assertInstanceOf(Club::class, $this->repository->createClub($this->validPayload()));
    }

    public function test_create_club_stores_null_logo_url_when_not_provided(): void
    {
        $club = $this->repository->createClub($this->validPayload());

        $this->assertNull($club->logo_url);
    }

    public function test_create_club_stores_logo_url_when_provided(): void
    {
        $club = $this->repository->createClub($this->validPayload(['logo_url' => 'logos/club.png']));

        $this->assertSame('logos/club.png', $club->logo_url);
    }

    public function test_create_club_stores_phone_number_when_provided(): void
    {
        $club = $this->repository->createClub($this->validPayload(['phone_number' => '+977 9800000000']));

        $this->assertDatabaseHas('clubs', ['id' => $club->id, 'phone_number' => '+977 9800000000']);
    }

    public function test_create_club_stores_null_phone_number_when_not_provided(): void
    {
        $this->assertNull($this->repository->createClub($this->validPayload())->phone_number);
    }

    public function test_create_club_without_address_has_null_address_id(): void
    {
        $club = $this->repository->createClub(['name' => 'No Address Club', 'abbreviation' => 'NAC']);

        $this->assertNull($club->address_id);
    }

    // ──────────────────────────────────────────────────────────────
    // updateClub
    // ──────────────────────────────────────────────────────────────

    public function test_update_club_updates_club_fields(): void
    {
        $club = Club::factory()->create();

        $this->repository->updateClub($this->validPayload(['name' => 'Updated Club', 'abbreviation' => 'UC']), $club->id);

        $this->assertDatabaseHas('clubs', ['id' => $club->id, 'name' => 'Updated Club', 'abbreviation' => 'UC']);
    }

    public function test_update_club_updates_the_linked_address(): void
    {
        $club = Club::factory()->create();

        $this->repository->updateClub($this->validPayload(['city' => 'Pokhara']), $club->id);

        $this->assertDatabaseHas('addresses', ['id' => $club->address_id, 'city' => 'Pokhara']);
    }

    public function test_update_club_returns_updated_club_instance(): void
    {
        $club = Club::factory()->create();

        $result = $this->repository->updateClub($this->validPayload(['name' => 'Renamed Club']), $club->id);

        $this->assertInstanceOf(Club::class, $result);
        $this->assertSame('Renamed Club', $result->name);
    }

    public function test_update_club_preserves_existing_logo_url_when_not_in_payload(): void
    {
        $club = Club::factory()->create(['logo_url' => 'logos/original.png']);

        $this->repository->updateClub($this->validPayload(), $club->id);

        $this->assertDatabaseHas('clubs', ['id' => $club->id, 'logo_url' => 'logos/original.png']);
    }

    public function test_update_club_replaces_logo_url_when_provided_in_payload(): void
    {
        $club = Club::factory()->create(['logo_url' => 'logos/original.png']);

        $this->repository->updateClub($this->validPayload(['logo_url' => 'logos/updated.png']), $club->id);

        $this->assertDatabaseHas('clubs', ['id' => $club->id, 'logo_url' => 'logos/updated.png']);
    }

    // ──────────────────────────────────────────────────────────────
    // deleteClub
    // ──────────────────────────────────────────────────────────────

    public function test_delete_club_soft_deletes_the_club(): void
    {
        $club = Club::factory()->create();
        $this->repository->deleteClub($club->id);
        $this->assertSoftDeleted('clubs', ['id' => $club->id]);
    }

    public function test_delete_club_soft_deletes_the_linked_address(): void
    {
        $club = Club::factory()->create();
        $addressId = $club->address_id;
        $this->repository->deleteClub($club->id);
        $this->assertSoftDeleted('addresses', ['id' => $addressId]);
    }

    public function test_delete_club_returns_true(): void
    {
        $this->assertTrue($this->repository->deleteClub(Club::factory()->create()->id));
    }

    public function test_delete_club_does_not_affect_other_clubs(): void
    {
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();
        $this->repository->deleteClub($clubA->id);
        $this->assertDatabaseHas('clubs', ['id' => $clubB->id, 'deleted_at' => null]);
    }

    // ──────────────────────────────────────────────────────────────
    // getById
    // ──────────────────────────────────────────────────────────────

    public function test_get_by_id_returns_club_with_address_loaded(): void
    {
        $result = $this->repository->getById(Club::factory()->create()->id);
        $this->assertInstanceOf(Club::class, $result);
        $this->assertTrue($result->relationLoaded('address'));
    }

    public function test_get_by_id_returns_correct_club(): void
    {
        $club = Club::factory()->create(['name' => 'Specific Club']);
        $result = $this->repository->getById($club->id);
        $this->assertSame($club->id, $result->id);
        $this->assertSame('Specific Club', $result->name);
    }

    public function test_get_by_id_throws_for_nonexistent_id(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->getById((string) Str::ulid());
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Club',
            'abbreviation' => 'TC',
            'address_line_1' => '1 Main Street',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'country_code' => 'NP',
        ], $overrides);
    }
}
