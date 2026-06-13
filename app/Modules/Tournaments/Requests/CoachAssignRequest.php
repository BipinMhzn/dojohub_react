<?php

namespace App\Modules\Tournaments\Requests;

use BaseSystem\Requests\BaseWebRequest;

/**
 * Class CoachAssignRequest
 */
final class CoachAssignRequest extends BaseWebRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coach_id' => ['required', 'string', 'exists:coaches,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'coach_id.required' => 'A coach must be selected.',
            'coach_id.exists' => 'The selected coach does not exist.',
        ];
    }
}
