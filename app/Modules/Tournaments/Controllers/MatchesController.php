<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
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
    /**
     * MatchesController constructor.
     */
    public function __construct(
        private readonly RegistrationInterface $registrationRepository,
        private readonly BracketServiceInterface $bracketService,
    ) {}

    /**
     * Display tournament bracket
     */
    public function index(string $tournamentId): Response
    {
        $tournament = $this->registrationRepository->getTournamentById($tournamentId);
        $bracketStructure = $this->bracketService->getBracketStructure($tournament);

        return $this->render('tournaments/matches/index', [
            'tournament' => $tournament,
            'bracket' => empty($bracketStructure['brackets']) ? null : $bracketStructure,
        ]);
    }

    /**
     * Generate bracket for tournament
     */
    public function generate(string $tournamentId): RedirectResponse
    {
        $tournament = $this->registrationRepository->getTournamentById($tournamentId);

        // Check if bracket already exists
        if ($tournament->matches()->count() > 0) {
            return back()->with('error', 'Bracket already generated for this tournament.');
        }

        try {
            $this->bracketService->generateBracket($tournament);

            return to_route('tournaments.matches.index', [$tournamentId])
                ->with('success', 'Bracket generated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record match result
     */
    public function updateResult(MatchResultRequest $request, string $tournamentId, string $matchId): RedirectResponse
    {
        $inputs = $request->validated();

        try {
            $this->bracketService->recordMatchResult(
                $matchId,
                $inputs['winner_id'],
                $inputs['player1_score'] ?? null,
                $inputs['player2_score'] ?? null
            );

            return to_route('tournaments.matches.index', [$tournamentId])
                ->with('success', 'Match result recorded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Schedule match
     */
    public function updateSchedule(MatchScheduleRequest $request, string $tournamentId, string $matchId): RedirectResponse
    {
        $inputs = $request->validated();

        try {
            $this->bracketService->scheduleMatch($matchId, $inputs['scheduled_at']);

            return to_route('tournaments.matches.index', [$tournamentId])
                ->with('success', 'Match scheduled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
