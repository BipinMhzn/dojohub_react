<?php

namespace BaseSystem\Foundation;

use BaseSystem\Constants\SystemConstants;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait SystemResponseTraits
 * @package base_system\Foundation
 */
trait SystemResponseTrait
{
    protected bool $api = true;

    /**
     * @param mixed $body
     * @param array $headers
     * @return JsonResponse
     */
    public function responseOk(mixed $body, array $headers = []): JsonResponse
    {
        return $this->baseResponse($body, Response::HTTP_OK, $headers);
    }

    /**
     * @param mixed $body
     * @param int $status
     * @param array $headers
     * @return JsonResponse
     */
    public function baseResponse(mixed $body, int $status = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        if ($body instanceof Collection) {
            $body = new Paginator($body->all(), $body->count());
        }
        $status = $this->validateStatusCode($status);

        return response()->json($body, $status)->withHeaders($headers);
    }

    public function validateStatusCode(int $code): int
    {
        $statusCodes = array(
            100, 101, 102,
            200, 201, 202, 203, 204, 205, 206, 207, 208, 226,
            300, 301, 302, 303, 304, 305, 306, 307, 308,
            400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 421, 422, 423, 424, 425, 426, 428, 429, 431, 451,
            500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511
        );
        if (in_array($code, $statusCodes, true)) {
            return $code;
        }

        return 500;
    }

    /**
     * @param array $body
     * @param array $headers
     * @return JsonResponse
     */
    public function responseValidationError(array $body = [], array $headers = []): JsonResponse
    {
        return $this->baseResponse($body, Response::HTTP_EXPECTATION_FAILED, $headers);
    }

    /**
     * @param string $codeText
     * @param array $headers
     * @return JsonResponse
     */
    public function responseServerError(string $codeText = SystemConstants::ERR_INTERNAL_SERVER_ERROR, array $headers = []): JsonResponse
    {
        return $this->baseResponseError(Response::HTTP_INTERNAL_SERVER_ERROR, $codeText, $headers);
    }

    /**
     * @param int $status
     * @param string $codeText
     * @param array $headers
     * @return JsonResponse
     */
    public function baseResponseError(int $status = Response::HTTP_INTERNAL_SERVER_ERROR, string $codeText = SystemConstants::ERR_INTERNAL_SERVER_ERROR, array $headers = []): JsonResponse
    {
        $status = $this->validateStatusCode($status);

        return response()->json([
            "message" => $this->translateCodeText($codeText),
            'code' => $codeText
        ], $status)->withHeaders($headers);
    }

    /**
     * @param string $codeText
     * @return string
     */
    private function translateCodeText(string $codeText): string
    {
        if (Lang::has('base-system.' . $codeText)) {
            return trans('base-system.' . $codeText);
        }
        if (Lang::has($codeText)) {
            return trans($codeText);
        }
        if (Lang::has('base-system::base-system.' . $codeText)) {
            return trans('base-system::base-system.' . $codeText);
        }

        return $codeText;
    }

    /**
     * @param string $codeText
     * @param array $headers
     * @return JsonResponse
     */
    public function responseUnAuthorize(string $codeText = SystemConstants::ERR_UNAUTHORIZED, array $headers = []): JsonResponse
    {
        return $this->baseResponseError(Response::HTTP_UNAUTHORIZED, $codeText, $headers);
    }

    /**
     * @param string $codeText
     * @param array $headers
     * @return JsonResponse
     */
    public function responseForbidden(string $codeText = SystemConstants::ERR_FORBIDDEN, array $headers = []): JsonResponse
    {
        return $this->baseResponseError(Response::HTTP_FORBIDDEN, $codeText, $headers);
    }

    /**
     * @param string $codeText
     * @param array $headers
     * @return JsonResponse
     */
    public function responseNotFound(string $codeText = SystemConstants::ERR_NOT_FOUND, array $headers = []): JsonResponse
    {
        return $this->baseResponseError(Response::HTTP_NOT_FOUND, $codeText, $headers);
    }

    /**
     * @param string $codeText
     * @param array $headers
     * @return JsonResponse
     */
    public function responseBadRequest(string $codeText = SystemConstants::ERR_BAD_REQUEST, array $headers = []): JsonResponse
    {
        return $this->baseResponseError(Response::HTTP_BAD_REQUEST, $codeText, $headers);
    }

    /**
     * @param string $codeText
     * @param array $headers
     * @return JsonResponse
     */
    public function responsePreConditionFailed(string $codeText = SystemConstants::ERR_PRECONDITION_FAILED, array $headers = []): JsonResponse
    {
        return $this->baseResponseError(Response::HTTP_PRECONDITION_FAILED, $codeText, $headers);
    }

    /**
     * @param string $codeText
     * @param array $headers
     * @return JsonResponse
     */
    public function responseConflict(string $codeText = SystemConstants::ERR_CONFLICT, array $headers = []): JsonResponse
    {
        return $this->baseResponseError(Response::HTTP_CONFLICT, $codeText, $headers);
    }

    /**
     * @param string $codeText
     * @param array $headers
     * @return JsonResponse
     */
    public function responseExpectationFailed(string $codeText = SystemConstants::ERR_EXPECTATION_FAILED, array $headers = []): JsonResponse
    {
        return $this->baseResponseError(Response::HTTP_EXPECTATION_FAILED, $codeText, $headers);
    }

    /**
     * @param string $codeText
     * @param array $headers
     * @return JsonResponse
     */
    public function responseTooManyAttempts(string $codeText = SystemConstants::ERR_TOO_MANY_LOGIN_ATTEMPT, array $headers = []): JsonResponse
    {
        return $this->baseResponseError(Response::HTTP_TOO_MANY_REQUESTS, $codeText, $headers);
    }
}
