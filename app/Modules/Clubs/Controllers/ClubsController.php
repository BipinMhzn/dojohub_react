<?php

namespace App\Modules\Clubs\Controllers;

use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Requests\ClubCreateRequest;
use App\Modules\Clubs\Requests\ClubUpdateRequest;
use BaseSystem\Requests\BaseListRequest;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Http\JsonResponse;
use BaseSystem\Controllers\BaseController;

/**
 * Class ClubsController
 * @package App\Modules\Clubs\Controllers
 */
final class ClubsController extends BaseController
{
    /**
     * ClubsController constructor.
     * @param ClubInterface $repository
     */
    public function __construct(private readonly ClubInterface $repository)
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
     * @param ClubCreateRequest $request
     * @return JsonResponse
     */
    public function store(ClubCreateRequest $request): JsonResponse
    {
        return $this->responseOk($this->repository->create($request->validated()));
    }

    /**
     * @param ClubUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(ClubUpdateRequest $request, int $id): JsonResponse
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
