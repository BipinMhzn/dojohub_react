<?php

namespace App\Modules\Tournaments\Interfaces;

use App\Modules\Tournaments\Models\TournamentMatch;
use BaseSystem\Interfaces\BaseInterface;
use Illuminate\Support\Collection;

/**
 * Interface MatchInterface
 */
interface MatchInterface extends BaseInterface
{
    public function getMatchById(string $matchId): TournamentMatch;

    public function getBracketMatches(string $bracketId): Collection;

    public function updateMatchSchedule(string $matchId, string $scheduledAt): TournamentMatch;
}
