<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Requests\MatchResultRequest;
use App\Modules\Tournaments\Requests\MatchScheduleRequest;
use BaseSystem\Controllers\BaseController;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class MatchesController
 * @package App\Modules\Tournaments\Controllers
 */
final class MatchesController extends BaseController
{
    /**
     * MatchesController constructor.
     *
     * @param RegistrationInterface $registrationRepository
     * @param BracketServiceInterface $bracketService
     */
    public function __construct(
        private readonly RegistrationInterface $registrationRepository,
        private readonly BracketServiceInterface $bracketService,
    ) {
    }

    /**
     * Display tournament bracket
     *
     * @param BaseRequest $request
     * @param string $tournamentId
     * @return Response
     */
    public function index(BaseRequest $request, string $tournamentId): Response
    {
        $tournament = $this->registrationRepository->getTournamentById($tournamentId);
        $bracketStructure = $this->bracketService->getBracketStructure($tournament);

        return Inertia::render('tournaments/matches/index', [
            'tournament' => $tournament,
            'bracket' => empty($bracketStructure['brackets']) ? null : $bracketStructure,
        ]);
    }

    /**
     * Generate bracket for tournament
     *
     * @param BaseRequest $request
     * @param string $tournamentId
     * @return RedirectResponse
     */
    public function generate(BaseRequest $request, string $tournamentId): RedirectResponse
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
     *
     * @param MatchResultRequest $request
     * @param string $tournamentId
     * @param string $matchId
     * @return RedirectResponse
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
     *
     * @param MatchScheduleRequest $request
     * @param string $tournamentId
     * @param string $matchId
     * @return RedirectResponse
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
