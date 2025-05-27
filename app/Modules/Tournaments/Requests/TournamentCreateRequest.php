<?php

namespace App\Modules\Tournaments\Requests;

use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use App\System\Tournament\Foundation\Enums\TournamentLevelEnum;
use BaseSystem\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Class TournamentCreateRequest
 * @package App\Modules\Tournaments\Requests
 */
class TournamentCreateRequest extends BaseRequest
{
    public function messages(): array
    {
        return [
            'start_at' => [
                'required' => 'The start date is required',
                'date_format' => 'The start date field must match the format Y-m-d H:i:s',
            ],
            'venue_id' => [
                'required' => 'The venue is required',
            ]
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'mimes:jpeg,jpg,png,svg,gif', 'max:' . 5 * 1024], //max 5MB
            'level' => ['required', 'integer', new Enum(TournamentLevelEnum::class)],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'date_format:Y-m-d H:i:s'],
            'weight_category' => ['required', 'string', 'max:50'],
            'min_weight' => ['required', 'integer', 'min:1', 'max:200'],
            'max_weight' => ['required', 'integer', 'min:1', 'max:200', 'gt:min_weight'],
            'age_category' => ['required', 'string', 'max:50'],
            'min_age' => ['required', 'integer', 'min:1', 'max:200'],
            'max_age' => ['required', 'integer', 'min:1', 'max:200', 'gt:min_age'],
            'gender' => ['required', new Enum(PlayerGenderEnum::class)],
            'venue_id' => ['required', 'string', Rule::exists('venues', 'id')->whereNull('deleted_at')],
        ];
    }
}
