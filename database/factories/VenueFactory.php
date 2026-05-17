<?php

namespace Database\Factories;

use App\System\Address\Models\Address;
use App\System\Venue\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $address = Address::factory()->create();

        return [
            'name' => $this->faker->company(),
            'address_id' => $address->id,
            'mat_count' => $this->faker->numberBetween(1, 10),
        ];
    }
}
