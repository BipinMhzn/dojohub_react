<?php

namespace App\System\Tournament\Foundation\Enums;

enum CompetitorResultEnum: int
{
    case Pending = 0;

    case Win = 1;

    case Loss = 2;

    case WalkoverWin = 3;

    case Disqualified = 4;
}
