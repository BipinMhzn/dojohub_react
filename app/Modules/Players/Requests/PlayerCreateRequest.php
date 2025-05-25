<?php

namespace App\Modules\Players\Requests;

use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Class PlayerCreateRequest
 * @package App\Modules\Players\Requests
 */
class PlayerCreateRequest extends BaseRequest
{

    public function messages(): array
    {
        return [
            'dob' => [
                'required' => 'The date of birth field is required',
                'before_or_equal' => 'The date of birth field must be a date before or equal to today.',
            ]
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', new Enum(PlayerGenderEnum::class)],
            'dob' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'weight' => ['required', 'numeric', 'min:1', 'max:500'],
            'profile_photo' => ['nullable', 'mimes:jpeg,jpg,png,svg,gif', 'max:' . 5 * 1024], //max 5MB
            'phone_number' => ['nullable', 'max:11', 'regex:/^(\+\d{1,2}\s?)?1?\-?\.?\s?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/'],
        ];
    }
}
