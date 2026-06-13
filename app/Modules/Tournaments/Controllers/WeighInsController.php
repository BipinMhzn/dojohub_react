<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Exceptions\WeighInNotAllowedException;
use App\Modules\Tournaments\Interfaces\WeighInInterface;
use App\Modules\Tournaments\Requests\WeighInStoreRequest;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;

/**
 * Class WeighInsController
 */
final class WeighInsController extends BaseWebController
{
    public function __construct(
        private readonly WeighInInterface $repository,
    ) {}

    public function store(WeighInStoreRequest $request, string $tournamentId, string $categoryId): RedirectResponse
    {
        try {
            $category = $this->repository->getCategoryById($categoryId);
            $this->repository->recordWeighIn($category, $request->validated());

            return to_route('tournaments.categories.registrations.index', [$tournamentId, $categoryId])
                ->with('success', 'Weigh-in recorded successfully.');
        } catch (TournamentCancelledException|WeighInNotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
