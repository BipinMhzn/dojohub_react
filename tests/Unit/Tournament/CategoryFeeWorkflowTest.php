<?php

namespace Tests\Unit\Tournament;

use App\Modules\TournamentCategories\Interfaces\TournamentCategoryInterface;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryFeeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): TournamentCategoryInterface
    {
        return app(TournamentCategoryInterface::class);
    }

    private function makeCategory(): TournamentCategory
    {
        return TournamentCategory::create([
            'tournament_id' => Tournament::factory()->create()->id,
            'gender' => 0,
            'age_category' => 8,
            'min_age' => 18,
            'max_age' => 34,
            'weight_category' => 0,
            'min_weight' => 60,
            'max_weight' => 90,
            'status' => CategoryStatusEnum::Draft,
        ]);
    }

    public function test_setting_a_fee_creates_a_fee_row(): void
    {
        $category = $this->makeCategory();

        $this->repository()->updateCategory($category->id, [
            'fee_amount' => 75,
            'fee_currency' => 'npr',
        ]);

        $this->assertDatabaseHas('tournament_registration_fees', [
            'tournament_category_id' => $category->id,
            'amount' => 75,
            'currency' => 'NPR',
        ]);
    }

    public function test_updating_the_fee_replaces_the_existing_row(): void
    {
        $category = $this->makeCategory();

        $this->repository()->updateCategory($category->id, ['fee_amount' => 75, 'fee_currency' => 'NPR']);
        $this->repository()->updateCategory($category->id, ['fee_amount' => 120, 'fee_currency' => 'USD']);

        $this->assertSame(1, $category->fee()->count());
        $this->assertEquals(120, $category->fee()->first()->amount);
        $this->assertSame('USD', $category->fee()->first()->currency);
    }

    public function test_clearing_the_fee_removes_the_row(): void
    {
        $category = $this->makeCategory();
        $this->repository()->updateCategory($category->id, ['fee_amount' => 75, 'fee_currency' => 'NPR']);

        $this->repository()->updateCategory($category->id, ['fee_amount' => null]);

        $this->assertNull($category->fee()->first());
    }

    public function test_update_without_fee_field_leaves_existing_fee_untouched(): void
    {
        $category = $this->makeCategory();
        $this->repository()->updateCategory($category->id, ['fee_amount' => 75, 'fee_currency' => 'NPR']);

        // A status-only style update that does not include the fee field must not wipe the fee.
        $this->repository()->updateCategory($category->id, ['name' => 'Renamed Category']);

        $this->assertEquals(75, $category->fee()->first()?->amount);
    }

    public function test_get_category_by_id_eager_loads_the_fee(): void
    {
        $category = $this->makeCategory();
        $this->repository()->updateCategory($category->id, ['fee_amount' => 40, 'fee_currency' => 'EUR']);

        $loaded = $this->repository()->getCategoryById($category->id);

        $this->assertTrue($loaded->relationLoaded('fee'));
        $this->assertEquals(40, $loaded->fee->amount);
    }
}
