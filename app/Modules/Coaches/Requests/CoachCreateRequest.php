<?php

namespace App\Modules\Coaches\Requests;

use BaseSystem\Requests\BaseWebRequest;

final class CoachCreateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'max:20', 'regex:/^\+?[\d\s\-().]{7,20}$/'],
            'user_id' => ['nullable', 'string', 'exists:users,id'],
        ];
    }
}
