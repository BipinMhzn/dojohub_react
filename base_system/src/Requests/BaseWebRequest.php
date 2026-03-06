<?php

namespace BaseSystem\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class BaseWebRequest
 */
abstract class BaseWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
