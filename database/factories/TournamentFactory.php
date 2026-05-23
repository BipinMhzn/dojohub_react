<?php

namespace Database\Factories;

use App\System\Tournament\Foundation\Enums\TournamentLevelEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\TournamentRuleSet\Models\TournamentRuleSet;
use App\System\Venue\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'level' => $this->faker->randomElement(TournamentLevelEnum::cases()),
            'status' => TournamentStatusEnum::Scheduled,
            'start_at' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'venue_id' => Venue::factory(),
        ];
    }

    public function withRuleSet(): static
    {
        return $this->state(fn () => [
            'rule_set_id' => TournamentRuleSet::first()?->id,
        ]);
    }

    public function withTimezone(string $timezone = 'Asia/Kathmandu'): static
    {
        return $this->state(fn () => [
            'timezone' => $timezone,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => TournamentStatusEnum::InProgress,
            'start_at' => now()->subDay(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => TournamentStatusEnum::Completed,
            'start_at' => now()->subWeek(),
            'end_at' => now()->subDays(5),
        ]);
    }
}
