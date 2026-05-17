<?php

namespace App\Modules\Players\Controllers;

use App\Modules\Players\Interfaces\PlayerInterface;
use App\Modules\Players\Requests\PlayerCreateRequest;
use App\Modules\Players\Requests\PlayerUpdateRequest;
use App\System\Foundation\Traits\FileUpload;
use BaseSystem\Controllers\BaseWebController;
use BaseSystem\Requests\BaseWebListRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * Class PlayersController
 */
final class PlayersController extends BaseWebController
{
    use FileUpload;

    /**
     * PlayersController constructor.
     */
    public function __construct(private readonly PlayerInterface $repository) {}

    /**
     * @param  BaseListRequest  $request
     */
    public function index(BaseWebListRequest $request): Response
    {
        return $this->render('players/index', [
            'players' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function create(): Response
    {
        return $this->render('players/create');
    }

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
        return $this->render('players/edit', [
            'player' => $this->repository->getById($player),
        ]);
    }

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

    public function destroy(string $player): RedirectResponse
    {
        $this->repository->destroy($player);

        return to_route('players.index')->with('success', 'Player deleted successfully.');
    }

    public function show(string $player): Response
    {
        return $this->render('players/show', [
            'player' => $this->repository->getById($player),
        ]);
    }
}
