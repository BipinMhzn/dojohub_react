<?php

namespace App\Modules\Tournaments\Requests;

use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rule;

/**
 * Class TournamentCreateRequest
 */
class TournamentRegistrationCreateRequest extends BaseWebRequest
{
    public function messages(): array
    {
        return [
            'player_id' => [
                'required' => 'The player is required',
            ],
            'club_id' => [
                'required' => 'The club is required',
            ],
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
