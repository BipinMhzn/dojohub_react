<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use App\System\Club\Models\Club;
use App\System\Player\Models\Player;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class TournamentRegistration extends AppBaseModel
{
    protected $fillable = [
        'tournament_category_id',
        'club_id',
        'player_id',
        'player_age',
        'status',
    ];

    protected array $sortableColumns = [
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatusEnum::class,
        ];
    }

    protected static function newFactory(): TournamentFactory
    {
        return TournamentFactory::new();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'tournament_category_id', 'id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'club_id', 'id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id', 'id');
    }

    public function weighIns(): HasMany
    {
        return $this->hasMany(TournamentWeighIn::class, 'registration_id', 'id');
    }

    public function latestWeighIn(): HasOne
    {
        return $this->hasOne(TournamentWeighIn::class, 'registration_id', 'id')->latestOfMany('weighed_at');
    }

    public function coaches(): HasMany
    {
        return $this->hasMany(TournamentRegistrationCoach::class, 'registration_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TournamentRegistrationPayment::class, 'registration_id', 'id');
    }

    /**
     * The fee that applies to this registration, resolved through its category.
     */
    public function fee(): HasOneThrough
    {
        return $this->hasOneThrough(
            TournamentRegistrationFee::class,
            TournamentCategory::class,
            'id',
            'tournament_category_id',
            'tournament_category_id',
            'id',
        );
    }
}
