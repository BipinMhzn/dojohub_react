<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use App\System\Tournament\Foundation\Enums\PaymentMethodEnum;
use Database\Factories\TournamentRegistrationPaymentFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegistrationPayment extends AppBaseModel
{
    protected $fillable = [
        'registration_id',
        'amount_paid',
        'currency',
        'paid_at',
        'method',
        'reference',
    ];

    protected static function newFactory(): TournamentRegistrationPaymentFactory
    {
        return TournamentRegistrationPaymentFactory::new();
    }

    protected array $sortableColumns = [
        'paid_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'paid_at' => 'datetime',
            'method' => PaymentMethodEnum::class,
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'registration_id', 'id');
    }
}
