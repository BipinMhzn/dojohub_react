<?php

namespace App\System\Tournament\Models;

use App\System\AppBaseModel;
use App\System\Coach\Models\Coach;
use Database\Factories\TournamentRegistrationCoachFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegistrationCoach extends AppBaseModel
{
    protected $fillable = [
        'registration_id',
        'coach_id',
    ];

    protected static function newFactory(): TournamentRegistrationCoachFactory
    {
        return TournamentRegistrationCoachFactory::new();
    }

    protected array $sortableColumns = [
        'created_at',
        'updated_at',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'registration_id', 'id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class, 'coach_id', 'id');
    }
}
