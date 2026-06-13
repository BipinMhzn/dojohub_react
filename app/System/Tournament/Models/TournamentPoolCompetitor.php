<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPoolCompetitor extends AppBaseModel
{
    protected $fillable = [
        'pool_id',
        'registration_id',
        'finishing_position',
    ];

    protected array $sortableColumns = [
        'finishing_position',
        'created_at',
        'updated_at',
    ];

    public function pool(): BelongsTo
    {
        return $this->belongsTo(TournamentPool::class, 'pool_id', 'id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'registration_id', 'id');
    }
}
