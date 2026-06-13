<?php

namespace App\Modules\Tournaments\Interfaces;

use App\Modules\Tournaments\Models\Tournament;
use BaseSystem\Interfaces\BaseInterface;

/**
 * Interface TournamentInterface
 */
interface TournamentInterface extends BaseInterface
{
    public function cancelTournament(string $tournamentId): Tournament;
}
