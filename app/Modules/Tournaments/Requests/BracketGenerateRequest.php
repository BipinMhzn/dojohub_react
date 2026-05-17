<?php

namespace App\Modules\Tournaments\Requests;

use App\System\Tournament\Foundation\Enums\BracketTypeEnum;
use App\System\Tournament\Foundation\Enums\SeedingMethodEnum;
use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Class BracketGenerateRequest
 */
class BracketGenerateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'integer', new Enum(BracketTypeEnum::class)],
            'seeding_method' => ['required', 'integer', new Enum(SeedingMethodEnum::class)],
        ];
    }
}
