<?php

namespace App\System\Tournament\Foundation\Enums;

enum TournamentLevelEnum: int
{
    case Local = 1;

    case Regional = 2;

    case National = 3;

    case International = 4;
}
