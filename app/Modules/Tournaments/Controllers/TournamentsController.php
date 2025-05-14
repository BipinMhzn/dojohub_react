<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Requests\TournamentCreateRequest;
use App\Modules\Tournaments\Requests\TournamentUpdateRequest;
use App\System\Foundation\Traits\FileUpload;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use BaseSystem\Controllers\BaseController;
use BaseSystem\Requests\BaseListRequest;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class TournamentsController
 * @package App\Modules\Tournaments\Controllers
 */
final class TournamentsController extends BaseController
{
    use FileUpload;

    private string $uploadDirectory = '/images/tournaments';

    /**
     * TournamentsController constructor.
     * @param TournamentInterface $repository
     */
    public function __construct(
        private readonly TournamentInterface $repository,
    )
    {
    }

    /**
     * @param BaseListRequest $request
     * @return Response
     */
    public function index(BaseListRequest $request): Response
    {
        return Inertia::render('tournaments/index', [
            'tournaments' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    /**
     * @param TournamentCreateRequest $request
     * @return RedirectResponse
     */
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

    /**
     * @param TournamentUpdateRequest $request
     * @param string $tournament
     * @return RedirectResponse
     */
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

    /**
     * @param BaseRequest $request
     * @param string $tournament
     * @return RedirectResponse
     */
    public function destroy(BaseRequest $request, string $tournament): RedirectResponse
    {
        $this->repository->destroy($tournament);

        return to_route('tournaments.index')->with('success', 'Tournament deleted successfully.');
    }

    /**
     * @param BaseRequest $request
     * @param string $tournament
     * @return Response
     */
    public function show(BaseRequest $request, string $tournament): Response
    {
        return Inertia::render('tournaments/show', [
            'tournament' => $this->repository->getById($tournament),
        ]);
    }
}
