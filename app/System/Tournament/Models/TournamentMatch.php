<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use App\System\Tournament\Foundation\Enums\BracketSectionEnum;
use App\System\Tournament\Foundation\Enums\MatchStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentMatch extends AppBaseModel
{
    protected $fillable = [
        'bracket_id',
        'bracket_section',
        'round_number',
        'round_name',
        'match_number',
        'mat_id',
        'scheduled_at',
        'completed_at',
        'status',
        'match_outcome',
        'is_golden_score',
        'golden_score_duration_seconds',
        'winning_competitor_id',
        'winner_next_match_id',
        'winner_next_position',
        'loser_next_match_id',
        'loser_next_position',
        'notes',
    ];

    protected array $sortableColumns = [
        'round_number',
        'match_number',
        'scheduled_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'bracket_section' => BracketSectionEnum::class,
            'status' => MatchStatusEnum::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function bracket(): BelongsTo
    {
        return $this->belongsTo(TournamentBracket::class, 'bracket_id', 'id');
    }

    public function winningCompetitor(): BelongsTo
    {
        return $this->belongsTo(TournamentMatchCompetitor::class, 'winning_competitor_id', 'id');
    }

    public function winnerNextMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'winner_next_match_id', 'id');
    }

    public function loserNextMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'loser_next_match_id', 'id');
    }

    public function competitors(): HasMany
    {
        return $this->hasMany(TournamentMatchCompetitor::class, 'match_id', 'id');
    }
}
