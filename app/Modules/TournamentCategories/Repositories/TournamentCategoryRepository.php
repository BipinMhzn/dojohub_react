<?php

namespace App\Modules\TournamentCategories\Repositories;

use App\Modules\TournamentCategories\Interfaces\TournamentCategoryInterface;
use App\Modules\TournamentCategories\Models\TournamentCategory;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Class TournamentCategoryRepository
 */
final class TournamentCategoryRepository extends BaseRepository implements TournamentCategoryInterface
{
    public function __construct(TournamentCategory $model)
    {
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
            ->with(['tournament.venue'])
            ->withCount('registrations')
            ->findOrFail($categoryId);
    }

    public function createCategory(string $tournamentId, array $validated): TournamentCategory
    {
        $validated['tournament_id'] = $tournamentId;
        $validated['status'] = CategoryStatusEnum::Draft;

        return $this->model->newQuery()->create($validated);
    }

    public function updateCategory(string $categoryId, array $validated): TournamentCategory
    {
        $category = $this->model->newQuery()->findOrFail($categoryId);
        $category->fill($validated);
        $category->save();

        return $category;
    }

    public function deleteCategory(string $categoryId): true
    {
        $category = $this->model->newQuery()->findOrFail($categoryId);
        $category->delete();

        return true;
    }
}
