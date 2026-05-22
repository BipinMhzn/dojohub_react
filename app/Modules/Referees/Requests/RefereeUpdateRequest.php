<?php

namespace App\Modules\Referees\Requests;

use App\System\Referee\Foundation\Enums\RefereeCertificationLevelEnum;
use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rules\Enum;

final class RefereeUpdateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'nationality_code' => ['nullable', 'string', 'size:2', 'exists:countries,code'],
            'certification_level' => ['nullable', new Enum(RefereeCertificationLevelEnum::class)],
            'user_id' => ['nullable', 'string', 'exists:users,id'],
        ];
    }
}
