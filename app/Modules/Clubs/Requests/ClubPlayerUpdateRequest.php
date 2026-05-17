<?php

namespace App\Modules\Clubs\Requests;

use App\System\ClubPlayer\Foundation\Enums\BeltRankEnum;
use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rule;

class ClubPlayerUpdateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'belt_rank' => ['nullable', 'integer', Rule::in(array_column(BeltRankEnum::cases(), 'value'))],
            'grade' => ['nullable', 'string', 'max:50'],
        ];
    }
}
