<?php

namespace App\Modules\Tournaments\Requests;

use BaseSystem\Requests\BaseRequest;

class MatchResultRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'winner_id' => 'required|ulid|exists:players,id',
            'player1_score' => 'nullable|integer|min:0',
            'player2_score' => 'nullable|integer|min:0',
        ];
    }
}
