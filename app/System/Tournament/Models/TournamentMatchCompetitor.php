<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use App\System\Tournament\Foundation\Enums\CompetitorPositionEnum;
use App\System\Tournament\Foundation\Enums\CompetitorResultEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatchCompetitor extends AppBaseModel
{
    protected $fillable = [
        'match_id',
        'registration_id',
        'position',
        'seed',
        'score',
        'penalties',
        'result',
    ];

    protected array $sortableColumns = [
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => CompetitorPositionEnum::class,
            'result' => CompetitorResultEnum::class,
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id', 'id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'registration_id', 'id');
    }
}
