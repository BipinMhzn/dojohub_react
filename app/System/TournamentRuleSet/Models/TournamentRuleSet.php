<?php

namespace App\System\TournamentRuleSet\Models;

use App\System\AppBaseModel;

class TournamentRuleSet extends AppBaseModel
{
    protected $fillable = [
        'name',
        'max_waza_ari',
        'max_shido',
        'match_duration_seconds',
        'golden_score_seconds',
    ];

    protected function casts(): array
    {
        return [
            'max_waza_ari' => 'integer',
            'max_shido' => 'integer',
            'match_duration_seconds' => 'integer',
            'golden_score_seconds' => 'integer',
        ];
    }
}
