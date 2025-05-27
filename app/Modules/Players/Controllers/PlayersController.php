<?php

namespace App\Modules\Players\Controllers;

use App\Modules\Players\Interfaces\PlayerInterface;
use App\Modules\Players\Requests\PlayerCreateRequest;
use App\Modules\Players\Requests\PlayerUpdateRequest;
use App\System\Foundation\Traits\FileUpload;
use BaseSystem\Controllers\BaseController;
use BaseSystem\Requests\BaseListRequest;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class PlayersController
 * @package App\Modules\Players\Controllers
 */
final class PlayersController extends BaseController
{
    use FileUpload;

    /**
     * PlayersController constructor.
     * @param PlayerInterface $repository
     */
    public function __construct(private readonly PlayerInterface $repository)
    {
    }

    /**
     * @param BaseListRequest $request
     * @return Response
     */
    public function index(BaseListRequest $request): Response
    {
        return Inertia::render('players/index', [
            'players' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('players/create');
    }

    /**
     * @param PlayerCreateRequest $request
     * @return RedirectResponse
     */
    public function store(PlayerCreateRequest $request): RedirectResponse
    {
        $inputs = $request->validated();
        if ($request->file('profile_photo')) {
            $profilePhoto = $this->uploadFile($request->file('profile_photo'), '/images/players');
            $inputs['profile_photo_url'] = $profilePhoto['path'];
            unset($inputs['profile_photo']);
        }

        $this->repository->create($inputs);

        return to_route('players.index')->with('success', 'Player created successfully.');
    }

    public function edit(string $player): Response
    {
        return Inertia::render('players/edit', [
            'player' => $this->repository->getById($player),
        ]);
    }

    /**
     * @param PlayerUpdateRequest $request
     * @param string $player
     * @return RedirectResponse
     */
    public function update(PlayerUpdateRequest $request, string $player): RedirectResponse
    {
        $inputs = $request->validated();
        if ($request->file('profile_photo')) {
            $profilePhoto = $this->uploadFile($request->file('profile_photo'), '/images/players');
            $inputs['profile_photo_url'] = $profilePhoto['path'];
            unset($inputs['profile_photo']);
        }
        $this->repository->update($player, $inputs);

        return to_route('players.index')->with('success', 'Player updated successfully.');
    }

    /**
     * @param BaseRequest $request
     * @param string $player
     * @return RedirectResponse
     */
    public function destroy(BaseRequest $request, string $player): RedirectResponse
    {
        $this->repository->destroy($player);

        return to_route('players.index')->with('success', 'Player deleted successfully.');
    }

    /**
     * @param BaseRequest $request
     * @param string $player
     * @return Response
     */
    public function show(BaseRequest $request, string $player): Response
    {
        return Inertia::render('players/show', [
            'player' => $this->repository->getById($player),
        ]);
    }
}
