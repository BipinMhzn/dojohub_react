<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentResult extends AppBaseModel
{
    protected $fillable = [
        'registration_id',
        'position',
    ];

    protected array $sortableColumns = [
        'position',
        'created_at',
        'updated_at',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'registration_id', 'id');
    }
}
