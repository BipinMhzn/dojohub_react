<?php

namespace App\Modules\Tournaments\Interfaces;

use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;

/**
 * Interface TournamentStatusResolverInterface
 *
 * Computes the tournament status from the statuses of its categories.
 */
interface TournamentStatusResolverInterface
{
    public function resolve(Tournament $tournament): TournamentStatusEnum;
}
