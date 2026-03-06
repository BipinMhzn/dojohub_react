<?php

namespace Database\Factories;

use App\System\Address\Models\Address;
use App\System\Club\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Club>
 */
class ClubFactory extends Factory
{
    protected $model = Club::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $address = Address::factory()->create();

        return [
            'name'         => $this->faker->company(),
            'abbreviation' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->faker->word()), 0, 3)),
            'address_id'   => $address->id,
            'phone_number' => $this->faker->phoneNumber(),
        ];
    }
}


