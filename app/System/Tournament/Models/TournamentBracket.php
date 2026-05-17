<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use App\System\Tournament\Foundation\Enums\BracketStatusEnum;
use App\System\Tournament\Foundation\Enums\BracketTypeEnum;
use App\System\Tournament\Foundation\Enums\SeedingMethodEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentBracket extends AppBaseModel
{
    protected $fillable = [
        'tournament_category_id',
        'type',
        'seeding_method',
        'competitor_count',
        'round_count',
        'status',
        'generated_at',
    ];

    protected array $sortableColumns = [
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => BracketTypeEnum::class,
            'seeding_method' => SeedingMethodEnum::class,
            'status' => BracketStatusEnum::class,
            'generated_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'tournament_category_id', 'id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'bracket_id', 'id');
    }
}
