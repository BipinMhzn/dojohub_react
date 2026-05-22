<?php

namespace Tests\Unit\Referee;

use App\Modules\Countries\Models\Country;
use App\Modules\Referees\Filters\RefereesFilter;
use App\Modules\Referees\Models\Referee;
use App\Modules\Referees\Repositories\RefereeRepository;
use App\System\Referee\Foundation\Enums\RefereeCertificationLevelEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefereeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private RefereeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal']);
        $this->repository = new RefereeRepository(new Referee);
    }

    // ──────────────────────────────────────────────────────────────
    // getFilter
    // ──────────────────────────────────────────────────────────────

    public function test_get_filter_returns_referees_filter_instance(): void
    {
        $this->assertInstanceOf(RefereesFilter::class, $this->repository->getFilter(Referee::query()));
    }

    // ──────────────────────────────────────────────────────────────
    // create
    // ──────────────────────────────────────────────────────────────

    public function test_create_persists_referee_to_database(): void
    {
        $this->repository->create($this->validPayload());

        $this->assertDatabaseHas('referees', ['full_name' => 'Test Referee']);
    }

    public function test_create_returns_referee_instance(): void
    {
        $this->assertInstanceOf(Referee::class, $this->repository->create($this->validPayload()));
    }

    public function test_create_stores_all_fields(): void
    {
        $referee = $this->repository->create($this->validPayload());

        $this->assertDatabaseHas('referees', [
            'id' => $referee->id,
            'full_name' => 'Test Referee',
            'nationality_code' => 'NP',
            'certification_level' => RefereeCertificationLevelEnum::National->value,
        ]);
    }

    public function test_create_allows_null_nationality(): void
    {
        $referee = $this->repository->create($this->validPayload(['nationality_code' => null]));

        $this->assertNull($referee->nationality_code);
    }

    public function test_create_allows_null_certification_level(): void
    {
        $referee = $this->repository->create($this->validPayload(['certification_level' => null]));

        $this->assertNull($referee->certification_level);
    }

    // ──────────────────────────────────────────────────────────────
    // update
    // ──────────────────────────────────────────────────────────────

    public function test_update_changes_full_name(): void
    {
        $referee = Referee::factory()->create();

        $this->repository->update($referee->id, $this->validPayload(['full_name' => 'Updated Name']));

        $this->assertDatabaseHas('referees', ['id' => $referee->id, 'full_name' => 'Updated Name']);
    }

    public function test_update_changes_certification_level(): void
    {
        $referee = Referee::factory()->create(['certification_level' => RefereeCertificationLevelEnum::Club->value]);

        $this->repository->update($referee->id, $this->validPayload(['certification_level' => RefereeCertificationLevelEnum::International->value]));

        $this->assertDatabaseHas('referees', [
            'id' => $referee->id,
            'certification_level' => RefereeCertificationLevelEnum::International->value,
        ]);
    }

    public function test_update_returns_updated_referee_instance(): void
    {
        $referee = Referee::factory()->create();

        $result = $this->repository->update($referee->id, $this->validPayload(['full_name' => 'Renamed']));

        $this->assertInstanceOf(Referee::class, $result);
        $this->assertSame('Renamed', $result->full_name);
    }

    // ──────────────────────────────────────────────────────────────
    // destroy
    // ──────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_the_referee(): void
    {
        $referee = Referee::factory()->create();
        $this->repository->destroy($referee->id);
        $this->assertSoftDeleted('referees', ['id' => $referee->id]);
    }

    public function test_destroy_does_not_affect_other_referees(): void
    {
        $refereeA = Referee::factory()->create();
        $refereeB = Referee::factory()->create();

        $this->repository->destroy($refereeA->id);

        $this->assertDatabaseHas('referees', ['id' => $refereeB->id, 'deleted_at' => null]);
    }

    // ──────────────────────────────────────────────────────────────
    // getById
    // ──────────────────────────────────────────────────────────────

    public function test_get_by_id_returns_correct_referee(): void
    {
        $referee = Referee::factory()->create(['full_name' => 'Specific Referee']);

        $result = $this->repository->getById($referee->id);

        $this->assertInstanceOf(Referee::class, $result);
        $this->assertSame($referee->id, $result->id);
        $this->assertSame('Specific Referee', $result->full_name);
    }

    public function test_get_by_id_eager_loads_country(): void
    {
        $result = $this->repository->getById(Referee::factory()->create()->id);

        $this->assertTrue($result->relationLoaded('country'));
    }

    public function test_get_by_id_eager_loads_user(): void
    {
        $result = $this->repository->getById(Referee::factory()->create()->id);

        $this->assertTrue($result->relationLoaded('user'));
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
            'full_name' => 'Test Referee',
            'nationality_code' => 'NP',
            'certification_level' => RefereeCertificationLevelEnum::National->value,
        ], $overrides);
    }
}
