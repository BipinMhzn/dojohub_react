<?php

namespace App\System\Tournament\Foundation\Enums;

enum MatchEventTypeEnum: int
{
    case Ippon = 0;

    case WazaAri = 1;

    case Shido = 2;

    case HansokuMake = 3;

    case HansokuMakeLight = 4;

    case Kinsa = 5;

    case Note = 6;
}
