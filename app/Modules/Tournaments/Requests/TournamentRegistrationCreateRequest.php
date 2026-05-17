<?php

namespace App\Modules\Tournaments\Requests;

use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rule;

/**
 * Class TournamentRegistrationCreateRequest
 */
class TournamentRegistrationCreateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'string', Rule::exists('players', 'id')->whereNull('deleted_at')],
            'club_id' => ['required', 'string', Rule::exists('clubs', 'id')->whereNull('deleted_at')],
        ];
    }
}
