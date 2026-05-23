<?php

namespace App\Modules\Clubs\Requests;

use BaseSystem\Requests\BaseWebListRequest;

final class ClubPlayerListRequest extends BaseWebListRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['nullable', 'string', 'in:active,ended,all'],
        ]);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            $this->merge(['status' => 'active']);
        }
    }
}
