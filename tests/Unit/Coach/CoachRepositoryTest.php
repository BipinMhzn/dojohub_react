<?php

namespace Tests\Unit\Coach;

use App\Modules\Coaches\Filters\CoachesFilter;
use App\Modules\Coaches\Models\Coach;
use App\Modules\Coaches\Repositories\CoachRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CoachRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CoachRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CoachRepository(new Coach);
    }

    // ──────────────────────────────────────────────────────────────
    // getFilter
    // ──────────────────────────────────────────────────────────────

    public function test_get_filter_returns_coaches_filter_instance(): void
    {
        $this->assertInstanceOf(CoachesFilter::class, $this->repository->getFilter(Coach::query()));
    }

    // ──────────────────────────────────────────────────────────────
    // create
    // ──────────────────────────────────────────────────────────────

    public function test_create_persists_coach_to_database(): void
    {
        $this->repository->create($this->validPayload());

        $this->assertDatabaseHas('coaches', ['full_name' => 'Test Coach']);
    }

    public function test_create_returns_coach_instance(): void
    {
        $this->assertInstanceOf(Coach::class, $this->repository->create($this->validPayload()));
    }

    public function test_create_stores_all_fields(): void
    {
        $coach = $this->repository->create($this->validPayload());

        $this->assertDatabaseHas('coaches', [
            'id' => $coach->id,
            'full_name' => 'Test Coach',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // update
    // ──────────────────────────────────────────────────────────────

    public function test_update_changes_full_name(): void
    {
        $coach = Coach::factory()->create();

        $this->repository->update($coach->id, $this->validPayload(['full_name' => 'Updated Name']));

        $this->assertDatabaseHas('coaches', ['id' => $coach->id, 'full_name' => 'Updated Name']);
    }

    public function test_update_returns_updated_coach_instance(): void
    {
        $coach = Coach::factory()->create();

        $result = $this->repository->update($coach->id, $this->validPayload(['full_name' => 'Renamed']));

        $this->assertInstanceOf(Coach::class, $result);
        $this->assertSame('Renamed', $result->full_name);
    }

    // ──────────────────────────────────────────────────────────────
    // destroy
    // ──────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_the_coach(): void
    {
        $coach = Coach::factory()->create();
        $this->repository->destroy($coach->id);
        $this->assertSoftDeleted('coaches', ['id' => $coach->id]);
    }

    public function test_destroy_does_not_affect_other_coaches(): void
    {
        $coachA = Coach::factory()->create();
        $coachB = Coach::factory()->create();

        $this->repository->destroy($coachA->id);

        $this->assertDatabaseHas('coaches', ['id' => $coachB->id, 'deleted_at' => null]);
    }

    // ──────────────────────────────────────────────────────────────
    // getById
    // ──────────────────────────────────────────────────────────────

    public function test_get_by_id_returns_correct_coach(): void
    {
        $coach = Coach::factory()->create(['full_name' => 'Specific Coach']);

        $result = $this->repository->getById($coach->id);

        $this->assertInstanceOf(Coach::class, $result);
        $this->assertSame($coach->id, $result->id);
        $this->assertSame('Specific Coach', $result->full_name);
    }

    public function test_get_by_id_eager_loads_user(): void
    {
        $result = $this->repository->getById(Coach::factory()->create()->id);

        $this->assertTrue($result->relationLoaded('user'));
    }

    public function test_get_by_id_eager_loads_active_memberships(): void
    {
        $result = $this->repository->getById(Coach::factory()->create()->id);

        $this->assertTrue($result->relationLoaded('activeMemberships'));
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
            'full_name' => 'Test Coach',
        ], $overrides);
    }
}
