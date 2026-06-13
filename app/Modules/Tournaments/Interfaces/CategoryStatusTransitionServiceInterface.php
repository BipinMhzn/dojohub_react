<?php

namespace App\Modules\Tournaments\Interfaces;

use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Models\TournamentCategory;

/**
 * Interface CategoryStatusTransitionServiceInterface
 *
 * Drives the category lifecycle: validates a transition, runs its side effects,
 * persists the new status, and re-syncs the parent tournament status.
 */
interface CategoryStatusTransitionServiceInterface
{
    public function transitionTo(TournamentCategory $category, CategoryStatusEnum $target): void;

    /**
     * @return list<CategoryStatusEnum> the targets reachable from the category's current status
     */
    public function allowedTargets(CategoryStatusEnum $from): array;
}
