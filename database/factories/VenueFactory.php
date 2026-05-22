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
        return [
            'name' => $this->faker->company(),
            'address_id' => Address::factory(),
        ];
    }
}
