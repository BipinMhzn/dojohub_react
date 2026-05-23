<?php

namespace App\Modules\Clubs\Controllers;

use App\Modules\Clubs\Interfaces\ClubCoachInterface;
use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Requests\ClubCoachAssignRequest;
use App\Modules\Clubs\Requests\ClubCoachEndRequest;
use App\Modules\Clubs\Requests\ClubCoachListRequest;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class ClubCoachesController extends BaseWebController
{
    public function __construct(
        private readonly ClubCoachInterface $repository,
        private readonly ClubInterface $clubRepository,
    ) {}

    public function index(ClubCoachListRequest $request, string $club): Response
    {
        return $this->render('clubs/coaches/index', [
            'club' => $this->clubRepository->getById($club),
            'memberships' => $this->repository->getClubCoaches($club, $request->validated()),
            'availableCoaches' => $this->repository->getAvailableCoaches($club),
            'filters' => $request->validated(),
        ]);
    }

    public function store(ClubCoachAssignRequest $request, string $club): RedirectResponse
    {
        try {
            $this->repository->assignCoach($club, $request->validated());

            return to_route('clubs.coaches.index', $club)->with('success', 'Coach assigned successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['coach_id' => $e->getMessage()]);
        }
    }

    public function update(ClubCoachEndRequest $request, string $club, string $membership): RedirectResponse
    {
        $this->repository->endMembership($club, $membership, $request->validated());

        return to_route('clubs.coaches.index', $club)->with('success', 'Membership ended successfully.');
    }
}
