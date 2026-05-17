<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use App\System\Tournament\Foundation\Enums\TournamentLevelEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Venue\Models\Venue;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends AppBaseModel
{
    protected $fillable = [
        'name',
        'description',
        'logo_url',
        'level',
        'start_at',
        'end_at',
        'status',
        'venue_id',
    ];

    protected array $sortableColumns = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'level' => TournamentLevelEnum::class,
            'status' => TournamentStatusEnum::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    protected static function newFactory(): TournamentFactory
    {
        return TournamentFactory::new();
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id', 'id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(TournamentCategory::class, 'tournament_id', 'id');
    }
}
