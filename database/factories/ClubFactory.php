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
        return [
            'name' => $this->faker->company(),
            'abbreviation' => strtoupper($this->faker->bothify('???')),
            'address_id' => Address::factory(),
            'phone_number' => null,
        ];
    }

    public function withoutAddress(): static
    {
        return $this->state(fn () => ['address_id' => null]);
    }
}
