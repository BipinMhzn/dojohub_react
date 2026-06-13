<?php

namespace Database\Factories;

use App\System\Coach\Models\Coach;
use App\System\Tournament\Models\TournamentRegistrationCoach;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentRegistrationCoach>
 *
 * Caller must supply `registration_id`.
 */
class TournamentRegistrationCoachFactory extends Factory
{
    protected $model = TournamentRegistrationCoach::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coach_id' => Coach::factory(),
        ];
    }
}
