<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Users\Interfaces\UserInterface;
use App\Modules\Users\Requests\UserCreateRequest;
use App\Modules\Users\Requests\UserUpdateRequest;
use BaseSystem\Requests\BaseListRequest;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Http\JsonResponse;
use BaseSystem\Controllers\BaseController;

/**
 * Class UsersController
 * @package App\Modules\Users\Controllers
 */
final class UsersController extends BaseController
{
    /**
     * UsersController constructor.
     * @param UserInterface $repository
     */
    public function __construct(private readonly UserInterface $repository)
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
     * @param UserCreateRequest $request
     * @return JsonResponse
     */
    public function store(UserCreateRequest $request): JsonResponse
    {
        return $this->responseOk($this->repository->create($request->validated()));
    }

    /**
     * @param UserUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UserUpdateRequest $request, int $id): JsonResponse
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
