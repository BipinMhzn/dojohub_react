<?php

namespace App\System\Tournament\Database\Models;

use App\System\AppBaseModel;
use App\System\Player\Database\Models\Player;
use App\System\Tournament\Foundation\Enums\MatchStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends AppBaseModel
{
    protected $fillable = [
        'tournament_id',
        'bracket_type',
        'round_number',
        'round_name',
        'match_order',
        'player1_id',
        'player2_id',
        'player1_seed',
        'player2_seed',
        'winner_id',
        'player1_score',
        'player2_score',
        'scheduled_at',
        'completed_at',
        'status',
        'winner_next_match_id',
        'loser_next_match_id',
        'winner_position',
        'loser_position',
        'notes',
    ];

    protected array $sortableColumns = [
        'round_number',
        'match_order',
        'scheduled_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'status' => MatchStatusEnum::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id', 'id');
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player1_id', 'id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player2_id', 'id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_id', 'id');
    }

    public function winnerNextMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'winner_next_match_id', 'id');
    }

    public function loserNextMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'loser_next_match_id', 'id');
    }

    /**
     * Accessor for checking if match has a bye
     */
    public function getHasByeAttribute(): bool
    {
        return $this->player1_id === null || $this->player2_id === null;
    }
}
