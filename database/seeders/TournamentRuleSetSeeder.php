<?php

namespace Database\Seeders;

use App\Modules\TournamentRuleSets\Models\TournamentRuleSet;
use Illuminate\Database\Seeder;

class TournamentRuleSetSeeder extends Seeder
{
    public function run(): void
    {
        TournamentRuleSet::firstOrCreate(
            ['name' => 'IJF 2024'],
            [
                'max_waza_ari' => 2,
                'max_shido' => 3,
                'match_duration_seconds' => 240,
                'golden_score_seconds' => null,
            ]
        );
    }
}
