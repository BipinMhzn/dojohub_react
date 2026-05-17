<?php

namespace App\Modules\Clubs\Requests;

use App\System\ClubPlayer\Foundation\Enums\BeltRankEnum;
use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rule;

class ClubPlayerAssignRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'string', 'exists:players,id'],
            'started_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'belt_rank' => ['nullable', 'integer', Rule::in(array_column(BeltRankEnum::cases(), 'value'))],
            'grade' => ['nullable', 'string', 'max:50'],
        ];
    }
}
