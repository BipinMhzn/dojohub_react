<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentWeighIn extends AppBaseModel
{
    protected $fillable = [
        'registration_id',
        'weight',
        'weighed_at',
        'weighed_by',
        'made_weight',
        'notes',
    ];

    protected array $sortableColumns = [
        'weighed_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'weighed_at' => 'datetime',
            'made_weight' => 'boolean',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'registration_id', 'id');
    }
}
