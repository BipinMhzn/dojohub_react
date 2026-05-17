<?php

namespace App\System\ClubPlayer\Models;

use App\System\AppBaseModel;
use App\System\Club\Models\Club;
use App\System\ClubPlayer\Foundation\Enums\BeltRankEnum;
use App\System\Player\Models\Player;
use Database\Factories\ClubPlayerFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubPlayer extends AppBaseModel
{
    protected $table = 'clubs_players';

    protected $fillable = [
        'club_id',
        'player_id',
        'started_at',
        'ended_at',
        'belt_rank',
        'grade',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'belt_rank' => BeltRankEnum::class,
        ];
    }

    protected static function newFactory(): ClubPlayerFactory
    {
        return ClubPlayerFactory::new();
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'club_id', 'id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id', 'id');
    }
}
