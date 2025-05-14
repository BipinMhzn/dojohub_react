<?php
namespace App\Modules\Venues\Requests;

use BaseSystem\Requests\BaseRequest;

/**
 * Class VenueCreateRequest
 * @package App\Modules\Venues\Requests
 */
class VenueCreateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'country' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:250'],
        ];
    }
}
