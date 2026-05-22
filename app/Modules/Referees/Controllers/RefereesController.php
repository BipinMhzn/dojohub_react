<?php

namespace App\Modules\Referees\Controllers;

use App\Modules\Countries\Models\Country;
use App\Modules\Referees\Interfaces\RefereeInterface;
use App\Modules\Referees\Requests\RefereeCreateRequest;
use App\Modules\Referees\Requests\RefereeUpdateRequest;
use App\System\Referee\Foundation\Enums\RefereeCertificationLevelEnum;
use BaseSystem\Controllers\BaseWebController;
use BaseSystem\Requests\BaseWebListRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class RefereesController extends BaseWebController
{
    public function __construct(private readonly RefereeInterface $repository) {}

    public function index(BaseWebListRequest $request): Response
    {
        return $this->render('referees/index', [
            'referees' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function create(): Response
    {
        return $this->render('referees/create', [
            'countries' => Country::orderBy('name')->get(['code', 'name']),
            'certificationLevels' => RefereeCertificationLevelEnum::cases(),
        ]);
    }

    public function store(RefereeCreateRequest $request): RedirectResponse
    {
        $this->repository->create($request->validated());

        return to_route('referees.index')->with('success', 'Referee created successfully.');
    }

    public function edit(string $referee): Response
    {
        return $this->render('referees/edit', [
            'referee' => $this->repository->getById($referee),
            'countries' => Country::orderBy('name')->get(['code', 'name']),
            'certificationLevels' => RefereeCertificationLevelEnum::cases(),
        ]);
    }

    public function update(RefereeUpdateRequest $request, string $referee): RedirectResponse
    {
        $this->repository->update($referee, $request->validated());

        return to_route('referees.index')->with('success', 'Referee updated successfully.');
    }

    public function destroy(string $referee): RedirectResponse
    {
        $this->repository->destroy($referee);

        return to_route('referees.index')->with('success', 'Referee deleted successfully.');
    }
}
