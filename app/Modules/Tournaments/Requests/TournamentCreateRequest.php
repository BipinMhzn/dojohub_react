<?php

namespace App\Modules\Tournaments\Requests;

use App\System\Tournament\Foundation\Enums\TournamentLevelEnum;
use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Class TournamentCreateRequest
 */
class TournamentCreateRequest extends BaseWebRequest
{
    public function messages(): array
    {
        return [
            'start_at.required' => 'The start date is required',
            'venue_id.required' => 'The venue is required',
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'mimes:jpeg,jpg,png,svg,gif', 'max:5120'],
            'level' => ['required', 'integer', new Enum(TournamentLevelEnum::class)],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date'],
            'venue_id' => ['required', 'string', Rule::exists('venues', 'id')->whereNull('deleted_at')],
        ];
    }
}
