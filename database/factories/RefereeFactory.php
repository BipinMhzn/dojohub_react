<?php

namespace Database\Factories;

use App\Modules\Countries\Models\Country;
use App\System\Referee\Foundation\Enums\RefereeCertificationLevelEnum;
use App\System\Referee\Models\Referee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referee>
 */
class RefereeFactory extends Factory
{
    protected $model = Referee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countryCode = Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal'])->code;

        return [
            'user_id' => null,
            'full_name' => $this->faker->name(),
            'nationality_code' => $countryCode,
            'certification_level' => $this->faker->randomElement(RefereeCertificationLevelEnum::cases())->value,
        ];
    }
}
