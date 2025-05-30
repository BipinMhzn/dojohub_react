<?php

namespace App\System\Tournament\Database\Models;

use App\System\AppBaseModel;
use App\System\Club\Database\Models\Club;
use App\System\Player\Database\Models\Player;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegistration extends AppBaseModel
{

    protected $fillable = [
        'tournament_id',
        'club_id',
        'player_id',
        'player_age',
        'player_weight',
    ];

    protected array $sortableColumns = [
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): TournamentFactory
    {
        return TournamentFactory::new();
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id', 'id');
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

