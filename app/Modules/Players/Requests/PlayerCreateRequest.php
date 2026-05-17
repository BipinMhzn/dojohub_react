<?php

namespace App\Modules\Players\Requests;

use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Class PlayerCreateRequest
 */
class PlayerCreateRequest extends BaseWebRequest
{
    public function messages(): array
    {
        return [
            'dob' => [
                'required' => 'The date of birth field is required',
                'before_or_equal' => 'The date of birth field must be a date before or equal to today.',
            ],
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', new Enum(PlayerGenderEnum::class)],
            'dob' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'weight' => ['required', 'numeric', 'min:1', 'max:500'],
            'nationality' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'profile_photo' => ['nullable', 'mimes:jpeg,jpg,png,svg,gif', 'max:'. 5 * 1024], // max 5MB
            'phone_number' => ['nullable', 'max:20', 'regex:/^\+?[\d\s\-().]{7,20}$/'],
        ];
    }
}
