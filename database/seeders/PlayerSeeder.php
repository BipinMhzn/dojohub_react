<?php

namespace Database\Seeders;

use App\System\Player\Database\Models\Player;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Player::factory()->count(50)->create();
    }
}
