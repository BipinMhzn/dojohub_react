<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Interfaces\CategoryInterface;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Models\TournamentResult;
use BaseSystem\Controllers\BaseWebController;
use Inertia\Response;

/**
 * Class ResultsController
 */
final class ResultsController extends BaseWebController
{
    public function __construct(
        private readonly TournamentInterface $tournamentRepository,
        private readonly CategoryInterface $categoryRepository,
        private readonly TournamentResult $resultModel,
    ) {}

    public function index(string $tournamentId, string $categoryId): Response
    {
        $results = $this->resultModel->newQuery()
            ->with(['registration.player', 'registration.club'])
            ->whereHas('registration', function ($query) use ($categoryId): void {
                $query->where('tournament_category_id', $categoryId);
            })
            ->orderBy('position')
            ->get();

        return $this->render('tournaments/results/index', [
            'tournament' => $this->tournamentRepository->getById($tournamentId),
            'category' => $this->categoryRepository->getCategoryById($categoryId),
            'results' => $results,
        ]);
    }
}
