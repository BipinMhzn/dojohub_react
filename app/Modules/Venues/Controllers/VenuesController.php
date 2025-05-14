<?php

namespace App\Modules\Venues\Controllers;

use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Requests\VenueCreateRequest;
use App\Modules\Venues\Requests\VenueUpdateRequest;
use BaseSystem\Controllers\BaseController;
use BaseSystem\Requests\BaseListRequest;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class VenuesController
 * @package App\Modules\Venues\Controllers
 */
final class VenuesController extends BaseController
{
    /**
     * VenuesController constructor.
     * @param VenueInterface $repository
     */
    public function __construct(private readonly VenueInterface $repository)
    {
    }

    /**
     * @param BaseListRequest $request
     * @return Response
     */
    public function index(BaseListRequest $request): Response
    {
        return Inertia::render('venues/index', [
            'venues' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function apiIndex(BaseListRequest $request): JsonResponse
    {
        return $this->responseOk($this->repository->getList($request->validated()));
    }

    /**
     * @param VenueCreateRequest $request
     * @return RedirectResponse
     */
    public function store(VenueCreateRequest $request): RedirectResponse
    {
        $this->repository->create($request->validated());

        return to_route('venues.index')->with('success', 'Venue created successfully.');
    }

    /**
     * @param VenueUpdateRequest $request
     * @param string $venue
     * @return RedirectResponse
     */
    public function update(VenueUpdateRequest $request, string $venue): RedirectResponse
    {
        $this->repository->update($venue, $request->validated());

        return to_route('venues.index')->with('success', 'Venue updated successfully.');
    }

    /**
     * @param BaseRequest $request
     * @param string $venue
     * @return RedirectResponse
     */
    public function destroy(BaseRequest $request, string $venue): RedirectResponse
    {
        $this->repository->destroy($venue);

        return to_route('venues.index')->with('success', 'Venue deleted successfully.');
    }
}
