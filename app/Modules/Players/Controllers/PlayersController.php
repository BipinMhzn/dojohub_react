<?php

namespace App\Modules\Players\Controllers;

use App\Modules\BeltRanks\Models\BeltRank;
use App\Modules\Countries\Models\Country;
use App\Modules\Players\Interfaces\PlayerInterface;
use App\Modules\Players\Requests\PlayerCreateRequest;
use App\Modules\Players\Requests\PlayerUpdateRequest;
use App\System\Foundation\Traits\FileUpload;
use BaseSystem\Controllers\BaseWebController;
use BaseSystem\Requests\BaseWebListRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class PlayersController extends BaseWebController
{
    use FileUpload;

    public function __construct(private readonly PlayerInterface $repository) {}

    public function index(BaseWebListRequest $request): Response
    {
        return $this->render('players/index', [
            'players' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function create(): Response
    {
        return $this->render('players/create', [
            'countries' => Country::orderBy('name')->get(['code', 'name']),
            'beltRanks' => BeltRank::orderBy('sort_order')->get(['id', 'code', 'name', 'color']),
        ]);
    }

    public function store(PlayerCreateRequest $request): RedirectResponse
    {
        $inputs = $request->validated();
        if ($request->file('profile_photo')) {
            $photo = $this->uploadFile($request->file('profile_photo'), '/images/players');
            $inputs['profile_photo_url'] = $photo['path'];
            unset($inputs['profile_photo']);
        }

        $this->repository->create($inputs);

        return to_route('players.index')->with('success', 'Player created successfully.');
    }

    public function edit(string $player): Response
    {
        return $this->render('players/edit', [
            'player' => $this->repository->getById($player),
            'countries' => Country::orderBy('name')->get(['code', 'name']),
            'beltRanks' => BeltRank::orderBy('sort_order')->get(['id', 'code', 'name', 'color']),
        ]);
    }

    public function update(PlayerUpdateRequest $request, string $player): RedirectResponse
    {
        $inputs = $request->validated();
        if ($request->file('profile_photo')) {
            $photo = $this->uploadFile($request->file('profile_photo'), '/images/players');
            $inputs['profile_photo_url'] = $photo['path'];
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
