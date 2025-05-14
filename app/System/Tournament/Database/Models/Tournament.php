<?php

namespace App\System\Tournament\Database\Models;

use App\System\AppBaseModel;
use App\System\Foundation\Traits\AvatarGenerator;
use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use App\System\Tournament\Foundation\Enums\TournamentLevelEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Venue\Database\Models\Venue;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tournament extends AppBaseModel
{
    use AvatarGenerator;

    protected $fillable = [
        'name',
        'description',
        'logo_url',
        'level',
        'start_at',
        'end_at',
        'weight_category',
        'min_weight',
        'max_weight',
        'age_category',
        'min_age',
        'max_age',
        'gender',
        'status',
        'venue_id',
    ];

    protected array $sortableColumns = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'level' => TournamentLevelEnum::class,
        'gender' => PlayerGenderEnum::class,
        'status' => TournamentStatusEnum::class,
    ];

    protected static function newFactory(): TournamentFactory
    {
        return TournamentFactory::new();
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (is_null($value)) {
                    return $this->generateAvatar($attributes['name']);
                }
                return $value;
            }
        );
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id', 'id');
    }
}

