<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use Database\Factories\TournamentRegistrationFeeFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegistrationFee extends AppBaseModel
{
    protected $fillable = [
        'tournament_category_id',
        'amount',
        'currency',
    ];

    protected static function newFactory(): TournamentRegistrationFeeFactory
    {
        return TournamentRegistrationFeeFactory::new();
    }

    protected array $sortableColumns = [
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'tournament_category_id', 'id');
    }
}
