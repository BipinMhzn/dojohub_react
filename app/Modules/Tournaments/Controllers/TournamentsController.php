<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Models\Venue;
use App\Modules\Tournaments\Requests\TournamentCreateRequest;
use App\Modules\Tournaments\Requests\TournamentUpdateRequest;
use App\System\Foundation\Traits\FileUpload;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use BaseSystem\Controllers\BaseWebController;
use BaseSystem\Requests\BaseWebListRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * Class TournamentsController
 */
final class TournamentsController extends BaseWebController
{
    use FileUpload;

    private string $uploadDirectory = '/images/tournaments';

    public function __construct(
        private readonly TournamentInterface $repository,
    ) {}

    public function index(BaseWebListRequest $request): Response
    {
        return $this->render('tournaments/index', [
            'tournaments' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function create(): Response
    {
        return $this->render('tournaments/create', [
            'venues' => Venue::all(['id', 'name']),
        ]);
    }

    public function store(TournamentCreateRequest $request): RedirectResponse
    {
        $inputs = $request->validated();
        if ($request->file('logo')) {
            $upload = $this->uploadFile($request->file('logo'), $this->uploadDirectory);
            $inputs['logo_url'] = $upload['path'];
            unset($inputs['logo']);
        }
        $inputs['status'] = TournamentStatusEnum::Scheduled;
        $this->repository->create($inputs);

        return to_route('tournaments.index')->with('success', 'Tournament created successfully.');
    }

    public function edit(string $tournament): Response
    {
        return $this->render('tournaments/edit', [
            'tournament' => $this->repository->getById($tournament),
            'venues' => Venue::all(['id', 'name']),
        ]);
    }

    public function update(TournamentUpdateRequest $request, string $tournament): RedirectResponse
    {
        $inputs = $request->validated();
        if ($request->file('logo')) {
            $upload = $this->uploadFile($request->file('logo'), $this->uploadDirectory);
            $inputs['logo_url'] = $upload['path'];
            unset($inputs['logo']);
        }
        $this->repository->update($tournament, $inputs);

        return to_route('tournaments.index')->with('success', 'Tournament updated successfully.');
    }

    public function destroy(string $tournament): RedirectResponse
    {
        $this->repository->destroy($tournament);

        return to_route('tournaments.index')->with('success', 'Tournament deleted successfully.');
    }
}
