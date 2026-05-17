<?php

namespace Database\Factories;

use App\System\Club\Models\Club;
use App\System\ClubPlayer\Foundation\Enums\BeltRankEnum;
use App\System\ClubPlayer\Models\ClubPlayer;
use App\System\Player\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClubPlayer>
 */
class ClubPlayerFactory extends Factory
{
    protected $model = ClubPlayer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'player_id' => Player::factory(),
            'started_at' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'ended_at' => null,
            'belt_rank' => fake()->boolean() ? fake()->randomElement(BeltRankEnum::cases())->value : null,
            'grade' => null,
        ];
    }

    /** State: membership already ended. */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => fake()->dateTimeBetween($attributes['started_at'], 'now'),
        ]);
    }
}
