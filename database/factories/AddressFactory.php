<?php

namespace Database\Factories;

use App\Modules\Countries\Models\Country;
use App\System\Address\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countryCode = Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal'])->code;

        return [
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => null,
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => null,
            'country_code' => $countryCode,
            'latitude' => null,
            'longitude' => null,
        ];
    }
}
