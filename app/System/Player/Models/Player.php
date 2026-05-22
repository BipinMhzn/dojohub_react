<?php

namespace App\System\Player\Models;

use App\System\Address\Models\Address;
use App\System\AppBaseModel;
use App\System\BeltRank\Models\BeltRank;
use App\System\Club\Models\Club;
use App\System\ClubPlayer\Models\ClubPlayer;
use App\System\Country\Models\Country;
use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends AppBaseModel
{
    protected $fillable = [
        'name',
        'gender',
        'dob',
        'weight',
        'nationality_code',
        'current_belt_rank_id',
        'address_id',
        'email',
        'profile_photo_url',
        'phone_number',
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
            'weight' => 'decimal:2',
        ];
    }

    protected static function newFactory(): PlayerFactory
    {
        return PlayerFactory::new();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'nationality_code', 'code');
    }

    public function currentBeltRank(): BelongsTo
    {
        return $this->belongsTo(BeltRank::class, 'current_belt_rank_id', 'id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id', 'id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ClubPlayer::class, 'player_id');
    }

    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class, 'clubs_players', 'player_id', 'club_id')
            ->withPivot(['id', 'started_at', 'ended_at', 'belt_rank_id', 'grade_certificate']);
    }
}
