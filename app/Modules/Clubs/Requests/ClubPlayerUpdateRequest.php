<?php

namespace App\Modules\Clubs\Requests;

use BaseSystem\Requests\BaseWebRequest;

class ClubPlayerUpdateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'belt_rank_id' => ['nullable', 'string', 'exists:belt_ranks,id'],
            'grade_certificate' => ['nullable', 'string', 'max:100'],
        ];
    }
}
