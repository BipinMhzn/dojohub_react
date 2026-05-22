<?php

namespace App\Modules\Venues\Requests;

use BaseSystem\Requests\BaseWebRequest;

class VenueCreateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'address_line_1' => ['required', 'string', 'max:250'],
            'address_line_2' => ['nullable', 'string', 'max:250'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
