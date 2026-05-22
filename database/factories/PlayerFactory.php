<?php

namespace Database\Factories;

use App\Modules\Countries\Models\Country;
use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use App\System\Player\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countryCode = Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal'])->code;

        return [
            'name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(PlayerGenderEnum::cases())->value,
            'dob' => $this->faker->date(),
            'weight' => $this->faker->randomFloat(2, 30, 150),
            'nationality_code' => $countryCode,
            'current_belt_rank_id' => null,
            'address_id' => null,
            'email' => $this->faker->optional()->safeEmail(),
            'profile_photo_url' => null,
            'phone_number' => $this->faker->optional()->numerify('+977 ##########'),
        ];
    }
}
