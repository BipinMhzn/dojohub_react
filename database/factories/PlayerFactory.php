<?php

namespace Database\Factories;

use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use App\System\Player\Models\Player;
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
            'weight' => $this->faker->randomFloat(2, 30, 150),
            'nationality' => 'Nepal',
            'email' => $this->faker->optional()->safeEmail(),
            'phone_number' => $this->faker->optional()->numerify('+977 ##########'),
        ];
    }
}
