<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Requests\TournamentRegistrationCreateRequest;
use App\Modules\Tournaments\Requests\TournamentRegistrationUpdateRequest;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use BaseSystem\Controllers\BaseWebController;
use BaseSystem\Requests\BaseWebListRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Response;

/**
 * Class TournamentsController
 */
final class RegistrationsController extends BaseWebController
{
    /**
     * TournamentsController constructor.
     */
    public function __construct(
        private readonly RegistrationInterface $repository,
    ) {}

    /**
     * @param  BaseListRequest  $request
     */
    public function index(BaseWebListRequest $request, string $tournamentId): Response
    {
        $tournament = $this->repository->getTournamentById($tournamentId);

        if ($tournament->status === TournamentStatusEnum::SCHEDULED) {
            $players = $this->repository->getPlayerList($tournament);
            $clubs = $this->repository->getClubList();
        } else {
            $players = [];
            $clubs = [];
        }

        return $this->render('tournaments/registrations/index', [
            'tournament' => $tournament,
            'registrations' => $this->repository->getTournamentRegistrations($tournament),
            'players' => $players,
            'clubs' => $clubs,
        ]);
    }

    public function store(TournamentRegistrationCreateRequest $request, string $tournamentId): RedirectResponse
    {
        $inputs = $request->validated();
        $tournament = $this->repository->getScheduledTournamentById($tournamentId);
        $player = $this->repository->getRegisteringPlayerById($tournament, $inputs['player_id']);
        $inputs['player_age'] = Carbon::parse($player->dob)->age;
        $inputs['player_weight'] = $player->weight;
        $inputs['tournament_id'] = $tournamentId;

        $this->repository->create($inputs);

        return to_route('tournaments.registrations.index', [$tournamentId])->with('success', 'Player registered successfully.');
    }

    public function update(TournamentRegistrationUpdateRequest $request, string $tournamentId, string $registrationId): RedirectResponse
    {
        $inputs = $request->validated();
        $tournament = $this->repository->getScheduledTournamentById($tournamentId);
        $player = $this->repository->getRegisteringPlayerById($tournament, $inputs['player_id'], $registrationId);
        $inputs['player_age'] = Carbon::parse($player->dob)->age;
        $inputs['player_weight'] = $player->weight;
        $inputs['tournament_id'] = $tournamentId;

        $this->repository->updateTournamentRegistration($tournament, $registrationId, $inputs);

        return to_route('tournaments.registrations.index', [$tournamentId])->with('success', 'Player registration updated successfully.');
    }

    public function destroy(string $tournamentId, string $registrationId): RedirectResponse
    {
        $tournament = $this->repository->getScheduledTournamentById($tournamentId);
        $this->repository->destroyTournamentRegistration($tournament, $registrationId);

        return to_route('tournaments.registrations.index', [$tournamentId])->with('success', 'Player registration deleted successfully.');
    }
}
