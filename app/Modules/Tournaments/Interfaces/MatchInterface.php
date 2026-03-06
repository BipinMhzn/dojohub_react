<?php

namespace App\Modules\Tournaments\Interfaces;

use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentMatch;
use BaseSystem\Interfaces\BaseInterface;
use Illuminate\Support\Collection;

interface MatchInterface extends BaseInterface
{
    public function getMatchById(string $matchId): TournamentMatch;

    public function getTournamentMatches(Tournament $tournament): Collection;

    public function updateMatchResult(string $matchId, array $inputs): TournamentMatch;

    public function updateMatchSchedule(string $matchId, string $scheduledAt): TournamentMatch;
}
