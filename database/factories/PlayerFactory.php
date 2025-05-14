<?php

namespace Database\Factories;

use App\System\Player\Database\Models\Player;
use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'gender' => $this->faker->randomElement(PlayerGenderEnum::class),
            'dob' => $this->faker->date(),
            'weight' => $this->faker->randomFloat(2, 1, 1000),
            'phone_number' => $this->faker->phoneNumber(),
        ];
    }
}
