<?php

namespace App\Modules\TournamentCategories\Interfaces;

use App\Modules\TournamentCategories\Models\TournamentCategory;
use BaseSystem\Interfaces\BaseInterface;
use Illuminate\Support\Collection;

/**
 * Interface TournamentCategoryInterface
 */
interface TournamentCategoryInterface extends BaseInterface
{
    public function getTournamentCategories(string $tournamentId): Collection;

    public function getCategoryById(string $categoryId): TournamentCategory;

    public function createCategory(string $tournamentId, array $validated): TournamentCategory;

    public function updateCategory(string $categoryId, array $validated): TournamentCategory;

    public function deleteCategory(string $categoryId): true;
}
