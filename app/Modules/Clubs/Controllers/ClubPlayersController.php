<?php

namespace App\Modules\Clubs\Controllers;

use App\Modules\Clubs\Interfaces\ClubPlayerInterface;
use App\Modules\Clubs\Requests\ClubPlayerAssignRequest;
use App\Modules\Clubs\Requests\ClubPlayerEndRequest;
use App\Modules\Clubs\Requests\ClubPlayerUpdateRequest;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;

final class ClubPlayersController extends BaseWebController
{
    public function __construct(private readonly ClubPlayerInterface $repository) {}

    public function store(ClubPlayerAssignRequest $request, string $club): RedirectResponse
    {
        $this->repository->assignPlayer($club, $request->validated());

        return to_route('clubs.show', $club)->with('success', 'Player assigned successfully.');
    }

    public function update(ClubPlayerEndRequest $request, string $club, string $membership): RedirectResponse
    {
        $this->repository->endMembership($club, $membership, $request->validated());

        return to_route('clubs.show', $club)->with('success', 'Membership ended successfully.');
    }

    public function patch(ClubPlayerUpdateRequest $request, string $club, string $membership): RedirectResponse
    {
        $this->repository->updateMembershipDetails($club, $membership, $request->validated());

        return to_route('clubs.show', $club)->with('success', 'Membership updated successfully.');
    }
}
