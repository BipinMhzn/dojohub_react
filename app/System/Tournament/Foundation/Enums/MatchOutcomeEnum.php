<?php

namespace App\System\Tournament\Foundation\Enums;

enum MatchOutcomeEnum: int
{
    case Ippon = 0;

    case WazaAri = 1;

    case Decision = 2;

    case HansokuMake = 3;

    case Walkover = 4;

    case Bye = 5;

    case GoldenScore = 6;
}
