<?php

namespace App\Modules\TournamentCategories\Controllers;

use App\Modules\TournamentCategories\Interfaces\TournamentCategoryInterface;
use App\Modules\TournamentCategories\Requests\TournamentCategoryCreateRequest;
use App\Modules\TournamentCategories\Requests\TournamentCategoryUpdateRequest;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * Class TournamentCategoriesController
 */
final class TournamentCategoriesController extends BaseWebController
{
    public function __construct(
        private readonly TournamentInterface $tournamentRepository,
        private readonly TournamentCategoryInterface $categoryRepository,
    ) {}

    public function index(string $tournamentId): Response
    {
        return $this->render('tournaments/categories/index', [
            'tournament' => $this->tournamentRepository->getById($tournamentId),
            'categories' => $this->categoryRepository->getTournamentCategories($tournamentId),
        ]);
    }

    public function options(string $tournamentId): JsonResponse
    {
        return response()->json($this->categoryRepository->getCategoryOptions($tournamentId));
    }

    public function create(string $tournamentId): Response
    {
        return $this->render('tournaments/categories/create', [
            'tournament' => $this->tournamentRepository->getById($tournamentId),
        ]);
    }

    public function store(TournamentCategoryCreateRequest $request, string $tournamentId): RedirectResponse
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

    public function update(TournamentCategoryUpdateRequest $request, string $tournamentId, string $categoryId): RedirectResponse
    {
        $this->categoryRepository->updateCategory($categoryId, $request->validated());

        return to_route('tournaments.categories.edit', [$tournamentId, $categoryId])
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(string $tournamentId, string $categoryId): RedirectResponse
    {
        $this->categoryRepository->deleteCategory($categoryId);

        return to_route('tournaments.categories.index', [$tournamentId])
            ->with('success', 'Category deleted successfully.');
    }
}
