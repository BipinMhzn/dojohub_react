<?php

namespace App\Modules\Tournaments\Requests;

use BaseSystem\Requests\BaseWebRequest;

/**
 * Class WeighInStoreRequest
 */
final class WeighInStoreRequest extends BaseWebRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'registration_id' => ['required', 'string', 'exists:tournament_registrations,id'],
            'weight' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registration_id.required' => 'A registration must be selected.',
            'weight.required' => 'The weight is required.',
            'weight.numeric' => 'The weight must be a number.',
        ];
    }
}
