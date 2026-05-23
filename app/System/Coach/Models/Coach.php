<?php

namespace App\System\Coach\Models;

use App\System\AppBaseModel;
use App\System\Club\Models\Club;
use App\System\ClubCoach\Models\ClubCoach;
use App\System\User\Models\User;
use Database\Factories\CoachFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coach extends AppBaseModel
{
    protected $table = 'coaches';

    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'phone_number',
    ];

    protected string $defaultSortColumn = 'full_name';

    protected array $sortableColumns = [
        'full_name',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): CoachFactory
    {
        return CoachFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ClubCoach::class, 'coach_id');
    }

    public function activeMemberships(): HasMany
    {
        return $this->hasMany(ClubCoach::class, 'coach_id')->whereNull('ended_at');
    }

    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class, 'clubs_coaches', 'coach_id', 'club_id')
            ->withPivot(['id', 'started_at', 'ended_at']);
    }
}
