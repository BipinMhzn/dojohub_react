<?php

namespace BaseSystem\Requests;

use BaseSystem\Foundation\SystemResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BaseRequest
 * @package base_system\Requests
 */
class BaseRequest extends FormRequest
{
    use SystemResponseTrait;

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param array $errors
     * @return Response
     */
    public function response(array $errors): Response
    {
        return $this->responseValidationError($errors);
    }

}
