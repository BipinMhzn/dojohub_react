<?php

namespace App\Modules\Venues\Requests;

use BaseSystem\Requests\BaseWebRequest;

/**
 * Class VenueCreateRequest
 */
class VenueCreateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'mat_count' => ['required', 'integer', 'min:1', 'max:99'],
            'country' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:250'],
        ];
    }
}
