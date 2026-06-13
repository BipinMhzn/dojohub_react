<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentPool extends AppBaseModel
{
    protected $fillable = [
        'bracket_id',
        'name',
    ];

    protected array $sortableColumns = [
        'name',
        'created_at',
        'updated_at',
    ];

    public function bracket(): BelongsTo
    {
        return $this->belongsTo(TournamentBracket::class, 'bracket_id', 'id');
    }

    public function competitors(): HasMany
    {
        return $this->hasMany(TournamentPoolCompetitor::class, 'pool_id', 'id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'pool_id', 'id');
    }
}
