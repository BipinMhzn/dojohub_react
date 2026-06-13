<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Requests\CoachAssignRequest;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;

/**
 * Class RegistrationCoachesController
 */
final class RegistrationCoachesController extends BaseWebController
{
    public function __construct(
        private readonly RegistrationInterface $repository,
    ) {}

    public function store(CoachAssignRequest $request, string $tournamentId, string $categoryId, string $registrationId): RedirectResponse
    {
        try {
            $category = $this->repository->getCategoryById($categoryId);
            $this->repository->assignCoach($category, $registrationId, $request->validated()['coach_id']);

            return back()->with('success', 'Coach assigned successfully.');
        } catch (TournamentCancelledException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(string $tournamentId, string $categoryId, string $registrationId, string $coachId): RedirectResponse
    {
        try {
            $category = $this->repository->getCategoryById($categoryId);
            $this->repository->removeCoach($category, $registrationId, $coachId);

            return back()->with('success', 'Coach removed successfully.');
        } catch (TournamentCancelledException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
