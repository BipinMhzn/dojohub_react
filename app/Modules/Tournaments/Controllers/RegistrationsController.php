<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Requests\TournamentRegistrationCreateRequest;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Response;

/**
 * Class RegistrationsController
 */
final class RegistrationsController extends BaseWebController
{
    public function __construct(
        private readonly RegistrationInterface $repository,
    ) {}

    public function index(string $tournamentId, string $categoryId): Response
    {
        $category = $this->repository->getCategoryById($categoryId);
        $canRegister = $category->status === CategoryStatusEnum::RegistrationOpen;

        return $this->render('tournaments/registrations/index', [
            'tournament' => $this->repository->getTournamentById($tournamentId),
            'category' => $category,
            'registrations' => $this->repository->getCategoryRegistrations($category),
            'players' => $canRegister ? $this->repository->getPlayerList($category) : [],
            'coaches' => $this->repository->getCoachList(),
        ]);
    }

    public function store(TournamentRegistrationCreateRequest $request, string $tournamentId, string $categoryId): RedirectResponse
    {
        $inputs = $request->validated();
        $category = $this->repository->getCategoryById($categoryId);
        $player = $this->repository->getRegisteringPlayerById($category, $inputs['player_id'], $inputs['club_id'] ?? null);

        $inputs['player_age'] = Carbon::parse($player->dob)->age;
        $inputs['club_id'] = $inputs['club_id'] ?? null;
        $inputs['tournament_category_id'] = $categoryId;
        $inputs['status'] = RegistrationStatusEnum::Registered;

        $this->repository->create($inputs);

        return to_route('tournaments.categories.registrations.index', [$tournamentId, $categoryId])
            ->with('success', 'Player registered successfully.');
    }

    public function destroy(string $tournamentId, string $categoryId, string $registrationId): RedirectResponse
    {
        try {
            $category = $this->repository->getCategoryById($categoryId);
            $this->repository->destroyTournamentRegistration($category, $registrationId);

            return to_route('tournaments.categories.registrations.index', [$tournamentId, $categoryId])
                ->with('success', 'Player registration removed successfully.');
        } catch (TournamentCancelledException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function withdraw(string $tournamentId, string $categoryId, string $registrationId): RedirectResponse
    {
        try {
            $category = $this->repository->getCategoryById($categoryId);
            $this->repository->withdrawRegistration($category, $registrationId);

            return to_route('tournaments.categories.registrations.index', [$tournamentId, $categoryId])
                ->with('success', 'Player withdrawn successfully.');
        } catch (TournamentCancelledException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
