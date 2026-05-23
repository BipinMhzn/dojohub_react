<?php

namespace App\Modules\Clubs\Requests;

use BaseSystem\Requests\BaseWebRequest;

class ClubCoachAssignRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'coach_id' => ['required', 'string', 'exists:coaches,id'],
            'started_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }
}
