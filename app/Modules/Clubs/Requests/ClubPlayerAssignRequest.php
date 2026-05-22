<?php

namespace App\Modules\Clubs\Requests;

use BaseSystem\Requests\BaseWebRequest;

class ClubPlayerAssignRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'string', 'exists:players,id'],
            'started_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'belt_rank_id' => ['nullable', 'string', 'exists:belt_ranks,id'],
            'grade_certificate' => ['nullable', 'string', 'max:100'],
        ];
    }
}
