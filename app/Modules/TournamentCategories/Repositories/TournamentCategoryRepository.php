<?php

namespace App\Modules\TournamentCategories\Repositories;

use App\Modules\TournamentCategories\Interfaces\TournamentCategoryInterface;
use App\Modules\TournamentCategories\Models\TournamentCategory;
use App\Modules\Tournaments\Models\TournamentRegistrationFee;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class TournamentCategoryRepository
 */
final class TournamentCategoryRepository extends BaseRepository implements TournamentCategoryInterface
{
    public function __construct(
        TournamentCategory $model,
        private readonly TournamentRegistrationFee $fee,
    ) {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): void {}

    public function getTournamentCategories(string $tournamentId): Collection
    {
        return $this->model->newQuery()
            ->where('tournament_id', $tournamentId)
            ->withCount('registrations')
            ->orderBy('gender')
            ->orderBy('age_category')
            ->orderBy('weight_category')
            ->get();
    }

    public function getCategoryOptions(string $tournamentId): Collection
    {
        return $this->model->newQuery()
            ->where('tournament_id', $tournamentId)
            ->orderBy('gender')
            ->orderBy('age_category')
            ->orderBy('weight_category')
            ->get(['id', 'tournament_id', 'name', 'gender', 'age_category', 'weight_category', 'status']);
    }

    public function getCategoryById(string $categoryId): TournamentCategory
    {
        return $this->model->newQuery()
            ->with(['tournament.venue', 'fee'])
            ->withCount('registrations')
            ->findOrFail($categoryId);
    }

    public function createCategory(string $tournamentId, array $validated): TournamentCategory
    {
        return DB::transaction(function () use ($tournamentId, $validated): TournamentCategory {
            $feeProvided = array_key_exists('fee_amount', $validated);
            [$feeAmount, $feeCurrency] = $this->extractFee($validated);

            $validated['tournament_id'] = $tournamentId;
            $validated['status'] = CategoryStatusEnum::Draft;

            $category = $this->model->newQuery()->create($validated);

            if ($feeProvided) {
                $this->syncFee($category->id, $feeAmount, $feeCurrency);
            }

            return $category;
        });
    }

    public function updateCategory(string $categoryId, array $validated): TournamentCategory
    {
        return DB::transaction(function () use ($categoryId, $validated): TournamentCategory {
            $feeProvided = array_key_exists('fee_amount', $validated);
            [$feeAmount, $feeCurrency] = $this->extractFee($validated);

            $category = $this->model->newQuery()->findOrFail($categoryId);
            $category->fill($validated);
            $category->save();

            if ($feeProvided) {
                $this->syncFee($category->id, $feeAmount, $feeCurrency);
            }

            return $category;
        });
    }

    /**
     * Pull the fee fields out of the validated category payload so they are not
     * mass-assigned onto the category model.
     *
     * @param  array<string, mixed>  $validated
     * @return array{0: float|null, 1: string|null}
     */
    private function extractFee(array &$validated): array
    {
        $amount = $validated['fee_amount'] ?? null;
        $currency = $validated['fee_currency'] ?? null;

        unset($validated['fee_amount'], $validated['fee_currency']);

        return [$amount === null ? null : (float) $amount, $currency];
    }

    /**
     * Upsert (or remove) the single registration fee attached to a category.
     */
    private function syncFee(string $categoryId, ?float $amount, ?string $currency): void
    {
        if ($amount === null) {
            $this->fee->newQuery()->where('tournament_category_id', $categoryId)->delete();

            return;
        }

        $this->fee->newQuery()->updateOrCreate(
            ['tournament_category_id' => $categoryId],
            ['amount' => $amount, 'currency' => strtoupper((string) $currency)],
        );
    }

    public function deleteCategory(string $categoryId): true
    {
        $category = $this->model->newQuery()->findOrFail($categoryId);
        $category->delete();

        return true;
    }
}
