<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use App\System\Tournament\Foundation\Enums\AgeCategoryEnum;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\WeightCategoryEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentCategory extends AppBaseModel
{
    protected $fillable = [
        'tournament_id',
        'name',
        'gender',
        'age_category',
        'min_age',
        'max_age',
        'weight_category',
        'min_weight',
        'max_weight',
        'status',
        'registration_open_at',
        'registration_close_at',
        'start_at',
        'end_at',
        'weigh_in_start_at',
        'weigh_in_end_at',
    ];

    protected array $sortableColumns = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'gender' => PlayerGenderEnum::class,
            'age_category' => AgeCategoryEnum::class,
            'weight_category' => WeightCategoryEnum::class,
            'status' => CategoryStatusEnum::class,
            'min_weight' => 'decimal:2',
            'max_weight' => 'decimal:2',
            'registration_open_at' => 'datetime',
            'registration_close_at' => 'datetime',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'weigh_in_start_at' => 'datetime',
            'weigh_in_end_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id', 'id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class, 'tournament_category_id', 'id');
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class, 'tournament_category_id', 'id');
    }
}
