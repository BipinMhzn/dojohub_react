<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Interfaces\BracketInterface;
use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Interfaces\CategoryInterface;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Requests\BracketGenerateRequest;
use App\Modules\Tournaments\Requests\MatchResultRequest;
use App\Modules\Tournaments\Requests\MatchScheduleRequest;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * Class MatchesController
 */
final class MatchesController extends BaseWebController
{
    public function __construct(
        private readonly TournamentInterface $tournamentRepository,
        private readonly CategoryInterface $categoryRepository,
        private readonly BracketInterface $bracketRepository,
        private readonly BracketServiceInterface $bracketService,
    ) {}

    public function index(string $tournamentId, string $categoryId): Response
    {
        $tournament = $this->tournamentRepository->getById($tournamentId);
        $category = $this->categoryRepository->getCategoryById($categoryId);
        $bracket = $this->bracketRepository->getBracketByCategory($categoryId);

        $bracketStructure = $bracket
            ? $this->bracketService->getBracketStructure($bracket)
            : null;

        return $this->render('tournaments/matches/index', [
            'tournament' => $tournament,
            'category' => $category,
            'bracket' => $bracket,
            'bracketStructure' => $bracketStructure,
        ]);
    }

    public function generate(BracketGenerateRequest $request, string $tournamentId, string $categoryId): RedirectResponse
    {
        $category = $this->categoryRepository->getCategoryById($categoryId);

        if ($this->bracketRepository->getBracketByCategory($categoryId)) {
            return back()->with('error', 'Bracket already generated for this category.');
        }

        try {
            $bracket = $this->bracketRepository->createBracket($categoryId, $request->validated());
            $this->bracketService->generateBracket($category, $bracket);

            return to_route('tournaments.categories.matches.index', [$tournamentId, $categoryId])
                ->with('success', 'Bracket generated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateResult(MatchResultRequest $request, string $tournamentId, string $categoryId, string $matchId): RedirectResponse
    {
        $inputs = $request->validated();

        try {
            $this->bracketService->recordMatchResult(
                $matchId,
                $inputs['winner_position'],
                $inputs['white_score'] ?? null,
                $inputs['white_penalties'] ?? null,
                $inputs['blue_score'] ?? null,
                $inputs['blue_penalties'] ?? null,
                $inputs['notes'] ?? null
            );

            return to_route('tournaments.categories.matches.index', [$tournamentId, $categoryId])
                ->with('success', 'Match result recorded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateSchedule(MatchScheduleRequest $request, string $tournamentId, string $categoryId, string $matchId): RedirectResponse
    {
        $inputs = $request->validated();

        try {
            $this->bracketService->scheduleMatch($matchId, $inputs['scheduled_at']);

            return to_route('tournaments.categories.matches.index', [$tournamentId, $categoryId])
                ->with('success', 'Match scheduled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
