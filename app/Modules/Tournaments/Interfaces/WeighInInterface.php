<?php

namespace App\Modules\Tournaments\Interfaces;

use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentWeighIn;
use BaseSystem\Interfaces\BaseInterface;

/**
 * Interface WeighInInterface
 */
interface WeighInInterface extends BaseInterface
{
    public function getCategoryById(string $categoryId): TournamentCategory;

    public function recordWeighIn(TournamentCategory $category, array $validated): TournamentWeighIn;
}
