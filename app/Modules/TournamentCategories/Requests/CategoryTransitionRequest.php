<?php

namespace App\Modules\TournamentCategories\Requests;

use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Class CategoryTransitionRequest
 */
class CategoryTransitionRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'target' => ['required', 'integer', new Enum(CategoryStatusEnum::class)],
        ];
    }

    public function targetStatus(): CategoryStatusEnum
    {
        return CategoryStatusEnum::from((int) $this->validated('target'));
    }
}
