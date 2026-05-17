<?php

namespace BaseSystem\Requests;

/**
 * Class BaseWebListRequest
 */
class BaseWebListRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:3', 'max:500'],
            'keyword' => ['nullable', 'string'],
        ];
    }
}
