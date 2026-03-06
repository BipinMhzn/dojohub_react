<?php

namespace App\Modules\Clubs\Controllers;

use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Requests\ClubCreateRequest;
use App\Modules\Clubs\Requests\ClubUpdateRequest;
use App\System\Foundation\Traits\FileUpload;
use BaseSystem\Controllers\BaseWebController;
use BaseSystem\Requests\BaseWebListRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * Class ClubsController
 */
final class ClubsController extends BaseWebController
{
    use FileUpload;

    private string $uploadDirectory = '/images/clubs';

    /**
     * ClubsController constructor.
     */
    public function __construct(private readonly ClubInterface $repository) {}

    /**
     * @param  BaseListRequest  $request
     */
    public function index(BaseWebListRequest $request): Response
    {
        return $this->render('clubs/index', [
            'clubs' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function create(): Response
    {
        return $this->render('clubs/create');
    }

    public function store(ClubCreateRequest $request): RedirectResponse
    {
        $inputs = $request->validated();
        if ($request->file('logo')) {
            $profilePhoto = $this->uploadFile($request->file('logo'), $this->uploadDirectory);
            $inputs['logo_url'] = $profilePhoto['path'];
            unset($inputs['logo']);
        }

        $this->repository->create($inputs);

        return to_route('clubs.index')->with('success', 'Club created successfully.');
    }

    public function edit(string $club): Response
    {
        return $this->render('clubs/edit', [
            'club' => $this->repository->getById($club),
        ]);
    }

    public function update(ClubUpdateRequest $request, string $club): RedirectResponse
    {
        $inputs = $request->validated();
        if ($request->file('logo')) {
            $profilePhoto = $this->uploadFile($request->file('logo'), $this->uploadDirectory);
            $inputs['logo_url'] = $profilePhoto['path'];
            unset($inputs['logo']);
        }
        $this->repository->update($club, $inputs);

        return to_route('clubs.index')->with('success', 'Club updated successfully.');
    }

    public function destroy(string $club): RedirectResponse
    {
        $this->repository->destroy($club);

        return to_route('clubs.index')->with('success', 'Club deleted successfully.');
    }

    public function show(string $club): Response
    {
        return $this->render('clubs/show', [
            'club' => $this->repository->getById($club),
        ]);
    }
}
