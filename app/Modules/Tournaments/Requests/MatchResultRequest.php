<?php

namespace App\Modules\Tournaments\Requests;

use BaseSystem\Requests\BaseWebRequest;

/**
 * Class MatchResultRequest
 */
class MatchResultRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'winner_position' => ['required', 'integer', 'in:0,1'],
            'white_score' => ['nullable', 'integer', 'min:0', 'max:2'],
            'white_penalties' => ['nullable', 'integer', 'min:0', 'max:3'],
            'blue_score' => ['nullable', 'integer', 'min:0', 'max:2'],
            'blue_penalties' => ['nullable', 'integer', 'min:0', 'max:3'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
