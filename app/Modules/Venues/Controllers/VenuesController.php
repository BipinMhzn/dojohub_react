<?php

namespace App\Modules\Venues\Controllers;

use App\Modules\Countries\Models\Country;
use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Requests\VenueCreateRequest;
use App\Modules\Venues\Requests\VenueUpdateRequest;
use BaseSystem\Controllers\BaseWebController;
use BaseSystem\Requests\BaseWebListRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class VenuesController extends BaseWebController
{
    public function __construct(private readonly VenueInterface $repository) {}

    public function index(BaseWebListRequest $request): Response
    {
        return $this->render('venues/index', [
            'venues' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function create(): Response
    {
        return $this->render('venues/create', [
            'countries' => Country::orderBy('name')->get(['code', 'name']),
        ]);
    }

    public function store(VenueCreateRequest $request): RedirectResponse
    {
        $this->repository->createVenue($request->validated());

        return to_route('venues.index')->with('success', 'Venue created successfully.');
    }

    public function edit(string $venue): Response
    {
        return $this->render('venues/edit', [
            'venue' => $this->repository->getById($venue),
            'countries' => Country::orderBy('name')->get(['code', 'name']),
        ]);
    }

    public function update(VenueUpdateRequest $request, string $venue): RedirectResponse
    {
        $this->repository->updateVenue($request->validated(), $venue);

        return to_route('venues.index')->with('success', 'Venue updated successfully.');
    }

    public function destroy(string $venue): RedirectResponse
    {
        $this->repository->deleteVenue($venue);

        return to_route('venues.index')->with('success', 'Venue deleted successfully.');
    }
}
