<?php

namespace App\Modules\Clubs\Requests;

use BaseSystem\Requests\BaseRequest;

/**
 * Class ClubCreateRequest
 * @package App\Modules\Clubs\Requests
 */
class ClubCreateRequest extends BaseRequest
{

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'uppercase', 'alpha', 'max:10'],
            'logo' => ['nullable', 'mimes:jpeg,jpg,png', 'max:' . 5 * 1024], //max 5MB
            'address' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'max:11', 'regex:/^(\+\d{1,2}\s?)?1?\-?\.?\s?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/'],
        ];
    }
}
