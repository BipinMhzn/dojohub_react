<?php

namespace App\Modules\TournamentCategories\Controllers;

use App\Modules\TournamentCategories\Interfaces\TournamentCategoryInterface;
use App\Modules\TournamentCategories\Requests\CategoryTransitionRequest;
use App\Modules\Tournaments\Exceptions\InvalidCategoryStatusTransitionException;
use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\CategoryStatusTransitionServiceInterface;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;

/**
 * Class CategoryTransitionController
 */
final class CategoryTransitionController extends BaseWebController
{
    public function __construct(
        private readonly TournamentCategoryInterface $categoryRepository,
        private readonly CategoryStatusTransitionServiceInterface $transitionService,
    ) {}

    public function transition(CategoryTransitionRequest $request, string $tournamentId, string $categoryId): RedirectResponse
    {
        try {
            $category = $this->categoryRepository->getCategoryById($categoryId);
            $target = $request->targetStatus();

            $this->transitionService->transitionTo($category, $target);

            return back()->with('success', "Category status updated to {$target->name}.");
        } catch (TournamentCancelledException $e) {
            return back()->with('error', $e->getMessage());
        } catch (InvalidCategoryStatusTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
