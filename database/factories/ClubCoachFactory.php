<?php

namespace Database\Factories;

use App\System\Club\Models\Club;
use App\System\ClubCoach\Models\ClubCoach;
use App\System\Coach\Models\Coach;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClubCoach>
 */
class ClubCoachFactory extends Factory
{
    protected $model = ClubCoach::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'coach_id' => Coach::factory(),
            'started_at' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'ended_at' => null,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => fake()->dateTimeBetween($attributes['started_at'], 'now'),
        ]);
    }
}
