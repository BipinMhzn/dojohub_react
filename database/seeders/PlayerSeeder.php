<?php

namespace Database\Seeders;

use App\System\Club\Models\Club;
use App\System\ClubPlayer\Models\ClubPlayer;
use App\System\Player\Models\Player;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clubIds = Club::query()->pluck('id');

        Player::factory()->count(50)->create()->each(function (Player $player) use ($clubIds): void {
            if ($clubIds->isEmpty()) {
                return;
            }

            // ~10% of players stay independent (no active club); the rest belong to one club,
            // and a handful belong to two so the registration club picker has options to show.
            $clubCount = fake()->randomElement([0, 1, 1, 1, 1, 1, 1, 1, 2, 2]);

            if ($clubCount === 0) {
                return;
            }

            foreach ($clubIds->random(min($clubCount, $clubIds->count())) as $clubId) {
                ClubPlayer::create([
                    'club_id' => $clubId,
                    'player_id' => $player->id,
                    'started_at' => now()->subMonths(fake()->numberBetween(1, 24)),
                    'ended_at' => null,
                ]);
            }
        });
    }
}
