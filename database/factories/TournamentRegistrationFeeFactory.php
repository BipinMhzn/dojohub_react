<?php

namespace Database\Factories;

use App\System\Tournament\Models\TournamentRegistrationFee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentRegistrationFee>
 *
 * Caller must supply `tournament_category_id`.
 */
class TournamentRegistrationFeeFactory extends Factory
{
    protected $model = TournamentRegistrationFee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(2, 5, 200),
            'currency' => $this->faker->randomElement(['NPR', 'USD', 'EUR']),
        ];
    }
}
