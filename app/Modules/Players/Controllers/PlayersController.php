<?php

namespace App\Modules\Players\Controllers;

use App\Modules\Players\Interfaces\PlayerInterface;
use App\Modules\Players\Requests\PlayerCreateRequest;
use App\Modules\Players\Requests\PlayerUpdateRequest;
use BaseSystem\Requests\BaseListRequest;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Http\JsonResponse;
use BaseSystem\Controllers\BaseController;

/**
 * Class PlayersController
 * @package App\Modules\Players\Controllers
 */
final class PlayersController extends BaseController
{
    /**
     * PlayersController constructor.
     * @param PlayerInterface $repository
     */
    public function __construct(private readonly PlayerInterface $repository)
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
     * @param PlayerCreateRequest $request
     * @return JsonResponse
     */
    public function store(PlayerCreateRequest $request): JsonResponse
    {
        return $this->responseOk($this->repository->create($request->validated()));
    }

    /**
     * @param PlayerUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(PlayerUpdateRequest $request, int $id): JsonResponse
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
