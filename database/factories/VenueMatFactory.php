<?php

namespace Database\Factories;

use App\System\Venue\Models\Venue;
use App\System\VenueMat\Models\VenueMat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueMat>
 */
class VenueMatFactory extends Factory
{
    protected $model = VenueMat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'mat_number' => $this->faker->numberBetween(1, 20),
            'display_name' => null,
        ];
    }

    public function withDisplayName(): static
    {
        return $this->state(fn () => [
            'display_name' => 'Tatami '.$this->faker->randomLetter(),
        ]);
    }
}
