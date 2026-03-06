<?php

namespace App\Modules\Clubs\Requests;

use BaseSystem\Requests\BaseWebRequest;

/**
 * Class ClubCreateRequest
 */
class ClubCreateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'uppercase', 'alpha', 'max:10'],
            'logo'         => ['nullable', 'mimes:jpeg,jpg,png,gif,svg', 'max:'. 5 * 1024], // max 5MB
            'phone_number' => ['nullable', 'max:20', 'regex:/^(\+\d{1,2}\s?)?\d[\d\s\-().]{6,18}\d$/'],
            'address'      => ['required', 'string', 'max:255'],
            'city'         => ['required', 'string', 'max:100'],
            'state'        => ['required', 'string', 'max:100'],
            'country'      => ['required', 'string', 'max:100'],
        ];
    }
}
