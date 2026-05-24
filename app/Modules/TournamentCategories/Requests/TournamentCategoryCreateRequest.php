<?php

namespace App\Modules\TournamentCategories\Requests;

use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use App\System\Tournament\Foundation\Enums\AgeCategoryEnum;
use App\System\Tournament\Foundation\Enums\WeightCategoryEnum;
use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Class TournamentCategoryCreateRequest
 */
class TournamentCategoryCreateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'integer', new Enum(PlayerGenderEnum::class)],
            'age_category' => ['required', 'integer', new Enum(AgeCategoryEnum::class)],
            'min_age' => ['required', 'integer', 'min:1', 'max:200'],
            'max_age' => ['required', 'integer', 'min:1', 'max:200', 'gte:min_age'],
            'weight_category' => ['required', 'integer', new Enum(WeightCategoryEnum::class)],
            'min_weight' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999.99'],
            'max_weight' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999.99', 'gte:min_weight'],
            'registration_open_at' => ['nullable', 'date'],
            'registration_close_at' => array_merge(
                ['nullable', 'date'],
                $this->filled('registration_open_at') ? ['after_or_equal:registration_open_at'] : []
            ),
            'start_at' => ['nullable', 'date'],
            'end_at' => array_merge(
                ['nullable', 'date'],
                $this->filled('start_at') ? ['after_or_equal:start_at'] : []
            ),
            'weigh_in_start_at' => ['nullable', 'date'],
            'weigh_in_end_at' => array_merge(
                ['nullable', 'date'],
                $this->filled('weigh_in_start_at') ? ['after_or_equal:weigh_in_start_at'] : []
            ),
        ];
    }
}
