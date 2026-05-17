<?php

namespace App\Modules\Tournaments\Requests;

use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use App\System\Tournament\Foundation\Enums\AgeCategoryEnum;
use App\System\Tournament\Foundation\Enums\WeightCategoryEnum;
use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Class CategoryCreateRequest
 */
class CategoryCreateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'integer', new Enum(PlayerGenderEnum::class)],
            'age_category' => ['required', 'integer', new Enum(AgeCategoryEnum::class)],
            'min_age' => ['required', 'integer', 'min:1', 'max:200'],
            'max_age' => ['required', 'integer', 'min:1', 'max:200', 'gt:min_age'],
            'weight_category' => ['required', 'integer', new Enum(WeightCategoryEnum::class)],
            'min_weight' => ['required', 'integer', 'min:1', 'max:999'],
            'max_weight' => ['required', 'integer', 'min:1', 'max:999', 'gt:min_weight'],
        ];
    }
}
