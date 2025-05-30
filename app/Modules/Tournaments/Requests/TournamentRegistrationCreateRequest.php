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
class TournamentRegistrationCreateRequest extends BaseRequest
{
    public function messages(): array
    {
        return [
            'player_id' => [
                'required' => 'The player is required',
            ],
            'club_id' => [
                'required' => 'The club is required',
            ]
        ];
    }

    public function rules(): array
    {
        return [
            'player_id' => ['required', 'string', Rule::exists('players', 'id')->whereNull('deleted_at')],
            'club_id' => ['required', 'string', Rule::exists('clubs', 'id')->whereNull('deleted_at')],
        ];
    }
}
