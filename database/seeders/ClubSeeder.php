<?php

namespace Database\Seeders;

use App\System\Club\Models\Club;
use Illuminate\Database\Seeder;

class ClubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Club::factory()->count(50)->create();
    }
}
