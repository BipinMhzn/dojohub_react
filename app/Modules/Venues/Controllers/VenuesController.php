<?php

namespace App\Modules\Venues\Controllers;

use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Requests\VenueCreateRequest;
use App\Modules\Venues\Requests\VenueUpdateRequest;
use BaseSystem\Controllers\BaseWebController;
use BaseSystem\Requests\BaseWebListRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * Class VenuesController
 */
final class VenuesController extends BaseWebController
{
    /**
     * VenuesController constructor.
     */
    public function __construct(private readonly VenueInterface $repository)
    {
    }

    public function index(BaseWebListRequest $request): Response
    {
        return $this->render('venues/index', [
            'venues' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function store(VenueCreateRequest $request): RedirectResponse
    {
        $this->repository->createVenue($request->validated());

        return to_route('venues.index')->with('success', 'Venue created successfully.');
    }

    public function create(): Response
    {
        return $this->render('venues/create');
    }

    public function edit(string $venue): Response
    {
        return $this->render('venues/edit', [
            'venue' => $this->repository->getById($venue),
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
