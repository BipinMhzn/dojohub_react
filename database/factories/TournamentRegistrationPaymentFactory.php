<?php

namespace Database\Factories;

use App\System\Tournament\Foundation\Enums\PaymentMethodEnum;
use App\System\Tournament\Models\TournamentRegistrationPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentRegistrationPayment>
 *
 * Caller must supply `registration_id`.
 */
class TournamentRegistrationPaymentFactory extends Factory
{
    protected $model = TournamentRegistrationPayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount_paid' => $this->faker->randomFloat(2, 5, 200),
            'currency' => 'NPR',
            'paid_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'method' => $this->faker->randomElement(PaymentMethodEnum::cases()),
            'reference' => $this->faker->optional()->bothify('TXN-####'),
        ];
    }
}
