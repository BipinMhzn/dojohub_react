<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Interfaces\CategoryInterface;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Requests\CategoryCreateRequest;
use App\Modules\Tournaments\Requests\CategoryUpdateRequest;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * Class CategoriesController
 */
final class CategoriesController extends BaseWebController
{
    public function __construct(
        private readonly TournamentInterface $tournamentRepository,
        private readonly CategoryInterface $categoryRepository,
    ) {}

    public function index(string $tournamentId): Response
    {
        return $this->render('tournaments/categories/index', [
            'tournament' => $this->tournamentRepository->getById($tournamentId),
            'categories' => $this->categoryRepository->getTournamentCategories($tournamentId),
        ]);
    }

    public function create(string $tournamentId): Response
    {
        return $this->render('tournaments/categories/create', [
            'tournament' => $this->tournamentRepository->getById($tournamentId),
        ]);
    }

    public function store(CategoryCreateRequest $request, string $tournamentId): RedirectResponse
    {
        $this->categoryRepository->createCategory($tournamentId, $request->validated());

        return to_route('tournaments.categories.index', [$tournamentId])
            ->with('success', 'Category created successfully.');
    }

    public function edit(string $tournamentId, string $categoryId): Response
    {
        return $this->render('tournaments/categories/edit', [
            'tournament' => $this->tournamentRepository->getById($tournamentId),
            'category' => $this->categoryRepository->getCategoryById($categoryId),
        ]);
    }

    public function update(CategoryUpdateRequest $request, string $tournamentId, string $categoryId): RedirectResponse
    {
        $this->categoryRepository->updateCategory($categoryId, $request->validated());

        return to_route('tournaments.categories.index', [$tournamentId])
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(string $tournamentId, string $categoryId): RedirectResponse
    {
        $this->categoryRepository->deleteCategory($categoryId);

        return to_route('tournaments.categories.index', [$tournamentId])
            ->with('success', 'Category deleted successfully.');
    }
}
