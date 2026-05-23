<?php

namespace App\System\Club\Models;

use App\System\Address\Models\Address;
use App\System\AppBaseModel;
use App\System\ClubCoach\Models\ClubCoach;
use App\System\ClubPlayer\Models\ClubPlayer;
use App\System\Coach\Models\Coach;
use App\System\Player\Models\Player;
use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends AppBaseModel
{
    protected $fillable = [
        'name',
        'abbreviation',
        'logo_url',
        'address_id',
        'phone_number',
    ];

    protected array $sortableColumns = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): ClubFactory
    {
        return ClubFactory::new();
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id', 'id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ClubPlayer::class, 'club_id');
    }

    public function activeMemberships(): HasMany
    {
        return $this->hasMany(ClubPlayer::class, 'club_id')->whereNull('ended_at');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'clubs_players', 'club_id', 'player_id')
            ->withPivot(['id', 'started_at', 'ended_at', 'belt_rank_id', 'grade_certificate']);
    }

    public function coachMemberships(): HasMany
    {
        return $this->hasMany(ClubCoach::class, 'club_id');
    }

    public function activeCoachMemberships(): HasMany
    {
        return $this->hasMany(ClubCoach::class, 'club_id')->whereNull('ended_at');
    }

    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(Coach::class, 'clubs_coaches', 'club_id', 'coach_id')
            ->withPivot(['id', 'started_at', 'ended_at']);
    }
}
