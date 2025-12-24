<?php

namespace App\Modules\Tournaments\Requests;

use BaseSystem\Requests\BaseRequest;

class MatchScheduleRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scheduled_at' => 'required|date|after:now',
        ];
    }
}
