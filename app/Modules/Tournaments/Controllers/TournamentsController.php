<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Database\Models\Venue;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
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

    /**
     * TournamentsController constructor.
     */
    public function __construct(
        private readonly TournamentInterface $repository,
    ) {}

    /**
     * @param  BaseListRequest  $request
     */
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
            $profilePhoto = $this->uploadFile($request->file('logo'), $this->uploadDirectory);
            $inputs['logo_url'] = $profilePhoto['path'];
            unset($inputs['logo']);
        }
        $inputs['status'] = TournamentStatusEnum::SCHEDULED;
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
            $profilePhoto = $this->uploadFile($request->file('logo'), $this->uploadDirectory);
            $inputs['logo_url'] = $profilePhoto['path'];
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

    public function show(string $tournament): Response
    {
        return $this->render('tournaments/show', [
            'tournament' => $this->repository->getById($tournament),
            'venues' => Venue::all(['id', 'name']),
        ]);
    }
}
