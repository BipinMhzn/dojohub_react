<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            BeltRankSeeder::class,
            TournamentRuleSetSeeder::class,
            RoleSeeder::class,
            DefaultAdminSeeder::class,
        ]);
    }
}
