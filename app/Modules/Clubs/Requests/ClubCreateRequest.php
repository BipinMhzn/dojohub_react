<?php

namespace App\Modules\Clubs\Requests;

use BaseSystem\Requests\BaseWebRequest;

class ClubCreateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'uppercase', 'alpha', 'max:10'],
            'logo' => ['nullable', 'mimes:jpeg,jpg,png,gif,svg', 'max:'.(5 * 1024)],
            'phone_number' => ['nullable', 'max:20', 'regex:/^(\+\d{1,2}\s?)?\d[\d\s\-().]{6,18}\d$/'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country_code' => ['nullable', 'string', 'size:2', 'exists:countries,code'],
        ];
    }
}
