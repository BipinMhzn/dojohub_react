<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Requests\TournamentCreateRequest;
use App\Modules\Tournaments\Requests\TournamentUpdateRequest;
use BaseSystem\Requests\BaseListRequest;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Http\JsonResponse;
use BaseSystem\Controllers\BaseController;

/**
 * Class TournamentsController
 * @package App\Modules\Tournaments\Controllers
 */
final class TournamentsController extends BaseController
{
    /**
     * TournamentsController constructor.
     * @param TournamentInterface $repository
     */
    public function __construct(private readonly TournamentInterface $repository)
    {
    }

    /**
     * @param BaseListRequest $request
     * @return JsonResponse
     */
    public function index(BaseListRequest $request): JsonResponse
    {
        return $this->responseOk($this->repository->getList($request->validated()));
    }

    /**
     * @param TournamentCreateRequest $request
     * @return JsonResponse
     */
    public function store(TournamentCreateRequest $request): JsonResponse
    {
        return $this->responseOk($this->repository->create($request->validated()));
    }

    /**
     * @param TournamentUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(TournamentUpdateRequest $request, int $id): JsonResponse
    {
        return $this->responseOk($this->repository->update($id, $request->validated()));
    }

    /**
     * @param BaseRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(BaseRequest $request, int $id): JsonResponse
    {
        return $this->responseOk($this->repository->destroy($id));
    }

    /**
     * @param BaseRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(BaseRequest $request, int $id): JsonResponse
    {
        return $this->responseOk($this->repository->getById($id));
    }
}
