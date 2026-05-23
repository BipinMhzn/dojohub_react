<?php

namespace App\Modules\Venues\Requests;

use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rule;

class VenueMatRequest extends BaseWebRequest
{
    public function rules(): array
    {
        $uniqueRule = Rule::unique('venue_mats', 'mat_number')
            ->where('venue_id', $this->route('venue'))
            ->whereNull('deleted_at');

        if ($this->route('mat')) {
            $uniqueRule = $uniqueRule->ignore($this->route('mat'));
        }

        return [
            'mat_number' => ['required', 'integer', 'min:1', 'max:99', $uniqueRule],
            'display_name' => ['nullable', 'string', 'max:50'],
        ];
    }
}
