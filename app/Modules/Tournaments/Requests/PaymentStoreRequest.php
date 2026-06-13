<?php

namespace App\Modules\Tournaments\Requests;

use App\System\Tournament\Foundation\Enums\PaymentMethodEnum;
use BaseSystem\Requests\BaseWebRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Class PaymentStoreRequest
 */
final class PaymentStoreRequest extends BaseWebRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount_paid' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'method' => ['required', 'integer', new Enum(PaymentMethodEnum::class)],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_paid.required' => 'The payment amount is required.',
            'currency.required' => 'The currency is required.',
            'method.required' => 'A payment method must be selected.',
        ];
    }
}
