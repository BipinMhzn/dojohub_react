<?php

namespace App\Modules\Tournaments\Requests;

use BaseSystem\Requests\BaseWebRequest;

/**
 * Class PoolCompetitorAssignRequest
 */
final class PoolCompetitorAssignRequest extends BaseWebRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'registration_id' => ['required', 'string', 'exists:tournament_registrations,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registration_id.required' => 'A competitor must be selected.',
        ];
    }
}
