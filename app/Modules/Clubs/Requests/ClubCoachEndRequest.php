<?php

namespace App\Modules\Clubs\Requests;

use App\Modules\Clubs\Models\ClubCoach;
use BaseSystem\Requests\BaseWebRequest;

class ClubCoachEndRequest extends BaseWebRequest
{
    public function rules(): array
    {
        $membership = ClubCoach::findOrFail($this->route('membership'));

        return [
            'ended_at' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$membership->started_at->format('Y-m-d'),
            ],
        ];
    }
}
