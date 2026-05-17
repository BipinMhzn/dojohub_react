<?php

namespace App\Modules\Clubs\Controllers;

use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Interfaces\ClubPlayerInterface;
use App\Modules\Clubs\Requests\ClubPlayerAssignRequest;
use App\Modules\Clubs\Requests\ClubPlayerEndRequest;
use App\Modules\Clubs\Requests\ClubPlayerUpdateRequest;
use BaseSystem\Controllers\BaseWebController;
use BaseSystem\Requests\BaseWebListRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class ClubPlayersController extends BaseWebController
{
    public function __construct(
        private readonly ClubPlayerInterface $repository,
        private readonly ClubInterface $clubRepository,
    ) {}

    public function index(BaseWebListRequest $request, string $club): Response
    {
        return $this->render('clubs/players/index', [
            'club' => $this->clubRepository->getById($club),
            'memberships' => $this->repository->getClubPlayers($club, $request->validated()),
            'availablePlayers' => $this->repository->getAvailablePlayers($club),
            'filters' => $request->validated(),
        ]);
    }

    public function store(ClubPlayerAssignRequest $request, string $club): RedirectResponse
    {
        $this->repository->assignPlayer($club, $request->validated());

        return to_route('clubs.players.index', $club)->with('success', 'Player assigned successfully.');
    }

    public function update(ClubPlayerEndRequest $request, string $club, string $membership): RedirectResponse
    {
        $this->repository->endMembership($club, $membership, $request->validated());

        return to_route('clubs.players.index', $club)->with('success', 'Membership ended successfully.');
    }

    public function patch(ClubPlayerUpdateRequest $request, string $club, string $membership): RedirectResponse
    {
        $this->repository->updateMembershipDetails($club, $membership, $request->validated());

        return to_route('clubs.players.index', $club)->with('success', 'Membership updated successfully.');
    }
}
