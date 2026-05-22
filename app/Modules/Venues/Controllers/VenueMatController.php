<?php

namespace App\Modules\Venues\Controllers;

use App\Modules\Venues\Interfaces\VenueMatInterface;
use App\Modules\Venues\Requests\VenueMatRequest;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;

final class VenueMatController extends BaseWebController
{
    public function __construct(private readonly VenueMatInterface $repository) {}

    public function store(VenueMatRequest $request, string $venue): RedirectResponse
    {
        $this->repository->createMat($venue, $request->validated());

        return to_route('venues.edit', $venue)->with('success', 'Mat added.');
    }

    public function update(VenueMatRequest $request, string $venue, string $mat): RedirectResponse
    {
        $this->repository->updateMat($mat, $request->validated());

        return to_route('venues.edit', $venue)->with('success', 'Mat updated.');
    }

    public function destroy(string $venue, string $mat): RedirectResponse
    {
        $this->repository->deleteMat($mat);

        return to_route('venues.edit', $venue)->with('success', 'Mat removed.');
    }
}
