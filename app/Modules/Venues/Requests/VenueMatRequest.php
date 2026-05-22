<?php

namespace App\Modules\Venues\Requests;

use BaseSystem\Requests\BaseWebRequest;

class VenueMatRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'mat_number' => ['required', 'integer', 'min:1', 'max:99'],
            'display_name' => ['nullable', 'string', 'max:50'],
        ];
    }
}
