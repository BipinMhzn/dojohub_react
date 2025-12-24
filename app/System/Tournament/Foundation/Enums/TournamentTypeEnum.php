<?php

namespace App\System\Tournament\Foundation\Enums;

enum TournamentTypeEnum: int
{
    case SINGLE_ELIMINATION = 0;

    case DOUBLE_ELIMINATION = 1;

    case ROUND_ROBIN = 2;
}
