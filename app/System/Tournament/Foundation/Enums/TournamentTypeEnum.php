<?php

namespace App\System\Tournament\Foundation\Enums;

/**
 * @deprecated Use BracketTypeEnum for tournament_brackets.type
 */
enum TournamentTypeEnum: int
{
    case SingleElimination = 0;

    case DoubleElimination = 1;

    case PoolFinal = 2;

    case Repechage = 3;
}
