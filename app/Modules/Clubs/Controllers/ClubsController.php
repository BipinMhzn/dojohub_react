<?php

namespace App\Modules\Clubs\Controllers;

use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Requests\ClubCreateRequest;
use App\Modules\Clubs\Requests\ClubUpdateRequest;
use App\System\Foundation\Traits\FileUpload;
use BaseSystem\Requests\BaseListRequest;
use BaseSystem\Requests\BaseRequest;
use BaseSystem\Controllers\BaseController;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class ClubsController
 * @package App\Modules\Clubs\Controllers
 */
final class ClubsController extends BaseController
{
    use FileUpload;

    private string $uploadDirectory = '/images/clubs';

    /**
     * ClubsController constructor.
     * @param ClubInterface $repository
     */
    public function __construct(private readonly ClubInterface $repository)
    {
    }

    /**
     * @param BaseListRequest $request
     * @return Response
     */
    public function index(BaseListRequest $request): Response
    {
        return Inertia::render('clubs/index', [
            'clubs' => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    /**
     * @param ClubCreateRequest $request
     * @return RedirectResponse
     */
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

    /**
     * @param ClubUpdateRequest $request
     * @param string $club
     * @return RedirectResponse
     */
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

    /**
     * @param BaseRequest $request
     * @param string $club
     * @return RedirectResponse
     */
    public function destroy(BaseRequest $request, string $club): RedirectResponse
    {
        $this->repository->destroy($club);

        return to_route('clubs.index')->with('success', 'Club deleted successfully.');
    }

    /**
     * @param BaseRequest $request
     * @param string $club
     * @return Response
     */
    public function show(BaseRequest $request, string $club): Response
    {
        return Inertia::render('clubs/show', [
            'club' => $this->repository->getById($club),
        ]);
    }
}
