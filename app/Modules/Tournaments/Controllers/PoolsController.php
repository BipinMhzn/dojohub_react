<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\TournamentCategories\Interfaces\TournamentCategoryInterface;
use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\BracketInterface;
use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Interfaces\PoolInterface;
use App\Modules\Tournaments\Requests\PoolCompetitorAssignRequest;
use App\Modules\Tournaments\Requests\PoolDistributeRequest;
use BaseSystem\Controllers\BaseWebController;
use Exception;
use Illuminate\Http\RedirectResponse;

/**
 * Class PoolsController
 */
final class PoolsController extends BaseWebController
{
    public function __construct(
        private readonly TournamentCategoryInterface $categoryRepository,
        private readonly BracketInterface $bracketRepository,
        private readonly BracketServiceInterface $bracketService,
        private readonly PoolInterface $poolRepository,
    ) {}

    public function redistribute(PoolDistributeRequest $request, string $tournamentId, string $categoryId): RedirectResponse
    {
        $category = $this->categoryRepository->getCategoryById($categoryId);
        $bracket = $this->bracketRepository->getBracketByCategory($categoryId);

        if (! $bracket) {
            return back()->with('error', 'Generate a Pool + Final bracket before distributing pools.');
        }

        try {
            $this->bracketService->generatePools($category, $bracket, (int) $request->validated()['pool_count']);

            return back()->with('success', 'Pools redistributed successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeCompetitor(PoolCompetitorAssignRequest $request, string $tournamentId, string $categoryId, string $poolId): RedirectResponse
    {
        try {
            $category = $this->categoryRepository->getCategoryById($categoryId);
            $this->poolRepository->assignCompetitor($category, $poolId, $request->validated()['registration_id']);

            return back()->with('success', 'Competitor assigned to pool.');
        } catch (TournamentCancelledException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroyCompetitor(string $tournamentId, string $categoryId, string $poolId, string $poolCompetitorId): RedirectResponse
    {
        try {
            $category = $this->categoryRepository->getCategoryById($categoryId);
            $this->poolRepository->removeCompetitor($category, $poolCompetitorId);

            return back()->with('success', 'Competitor removed from pool.');
        } catch (TournamentCancelledException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
