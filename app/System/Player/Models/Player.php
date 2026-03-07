<?php

namespace App\System\Player\Models;

use App\System\AppBaseModel;
use App\System\Club\Models\Club;
use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Player extends AppBaseModel
{
    protected $fillable = [
        'name',
        'gender',
        'dob',
        'weight',
        'nationality',
        'email',
        'profile_photo_url',
        'phone_number',
    ];

    protected array $sortableColumns = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'gender' => PlayerGenderEnum::class,
    ];

    protected static function newFactory(): PlayerFactory
    {
        return PlayerFactory::new();
    }

    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class, 'clubs_players', 'player_id', 'club_id')
            ->withPivot(['id', 'started_at', 'ended_at', 'belt_rank', 'grade']);
    }
}
