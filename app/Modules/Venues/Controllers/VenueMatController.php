<?php

namespace App\Modules\Venues\Controllers;

use App\Modules\Venues\Exceptions\VenueMatInUseException;
use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Interfaces\VenueMatInterface;
use App\Modules\Venues\Requests\VenueMatRequest;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class VenueMatController extends BaseWebController
{
    public function __construct(
        private readonly VenueMatInterface $repository,
        private readonly VenueInterface $venueRepository,
    ) {}

    public function index(string $venue): Response
    {
        return $this->render('venues/mats/index', [
            'venue' => $this->venueRepository->getById($venue),
        ]);
    }

    public function store(VenueMatRequest $request, string $venue): RedirectResponse
    {
        $this->repository->createMat($venue, $request->validated());

        return to_route('venues.mats.index', $venue)->with('success', 'Mat added.');
    }

    public function update(VenueMatRequest $request, string $venue, string $mat): RedirectResponse
    {
        $this->repository->updateMat($mat, $request->validated());

        return to_route('venues.mats.index', $venue)->with('success', 'Mat updated.');
    }

    public function destroy(string $venue, string $mat): RedirectResponse
    {
        try {
            $this->repository->deleteMat($mat);

            return to_route('venues.mats.index', $venue)->with('success', 'Mat removed.');
        } catch (VenueMatInUseException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
