<?php

namespace App\Modules\Clubs\Requests;

use App\Modules\Clubs\Models\ClubPlayer;
use BaseSystem\Requests\BaseWebRequest;

class ClubPlayerEndRequest extends BaseWebRequest
{
    public function rules(): array
    {
        $membership = ClubPlayer::findOrFail($this->route('membership'));

        return [
            'ended_at' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$membership->started_at->format('Y-m-d'),
            ],
        ];
    }
}
