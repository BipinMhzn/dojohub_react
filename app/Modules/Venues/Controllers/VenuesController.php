<?php

namespace App\Modules\Venues\Controllers;

use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Requests\VenueCreateRequest;
use App\Modules\Venues\Requests\VenueUpdateRequest;
use BaseSystem\Requests\BaseListRequest;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Http\JsonResponse;
use BaseSystem\Controllers\BaseController;

/**
 * Class VenuesController
 * @package App\Modules\Venues\Controllers
 */
final class VenuesController extends BaseController
{
    /**
     * VenuesController constructor.
     * @param VenueInterface $repository
     */
    public function __construct(private readonly VenueInterface $repository)
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
     * @param VenueCreateRequest $request
     * @return JsonResponse
     */
    public function store(VenueCreateRequest $request): JsonResponse
    {
        return $this->responseOk($this->repository->create($request->validated()));
    }

    /**
     * @param VenueUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(VenueUpdateRequest $request, int $id): JsonResponse
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
