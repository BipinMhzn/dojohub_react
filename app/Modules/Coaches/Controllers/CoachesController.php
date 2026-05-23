<?php

namespace App\Modules\Coaches\Controllers;

use App\Modules\Coaches\Interfaces\CoachInterface;
use App\Modules\Coaches\Requests\CoachCreateRequest;
use App\Modules\Coaches\Requests\CoachUpdateRequest;
use BaseSystem\Controllers\BaseWebController;
use BaseSystem\Requests\BaseWebListRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class CoachesController extends BaseWebController
{
    public function __construct(private readonly CoachInterface $repository) {}

    public function index(BaseWebListRequest $request): Response
    {
        return $this->render('coaches/index', [
            'coaches' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function create(): Response
    {
        return $this->render('coaches/create');
    }

    public function store(CoachCreateRequest $request): RedirectResponse
    {
        $this->repository->create($request->validated());

        return to_route('coaches.index')->with('success', 'Coach created successfully.');
    }

    public function edit(string $coach): Response
    {
        return $this->render('coaches/edit', [
            'coach' => $this->repository->getById($coach),
        ]);
    }

    public function update(CoachUpdateRequest $request, string $coach): RedirectResponse
    {
        $this->repository->update($coach, $request->validated());

        return to_route('coaches.index')->with('success', 'Coach updated successfully.');
    }

    public function destroy(string $coach): RedirectResponse
    {
        $this->repository->destroy($coach);

        return to_route('coaches.index')->with('success', 'Coach deleted successfully.');
    }
}
