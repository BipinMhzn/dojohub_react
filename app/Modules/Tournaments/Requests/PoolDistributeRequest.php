<?php

namespace App\Modules\Tournaments\Requests;

use BaseSystem\Requests\BaseWebRequest;

/**
 * Class PoolDistributeRequest
 */
final class PoolDistributeRequest extends BaseWebRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pool_count' => ['required', 'integer', 'min:1', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pool_count.required' => 'The number of pools is required.',
        ];
    }
}
