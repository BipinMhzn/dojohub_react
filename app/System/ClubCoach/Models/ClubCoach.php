<?php

namespace App\System\ClubCoach\Models;

use App\System\AppBaseModel;
use App\System\Club\Models\Club;
use App\System\Coach\Models\Coach;
use Database\Factories\ClubCoachFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubCoach extends AppBaseModel
{
    protected $table = 'clubs_coaches';

    protected $fillable = [
        'club_id',
        'coach_id',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ClubCoachFactory
    {
        return ClubCoachFactory::new();
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'club_id', 'id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class, 'coach_id', 'id');
    }
}
