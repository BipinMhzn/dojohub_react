<?php

namespace App\System\Tournament\Foundation\Enums;

enum AgeCategoryEnum: int
{
    case Preparation = 0;

    case SuperMini = 1;

    case Mini = 2;

    case Pupils = 3;

    case Juveniles = 4;

    case Cadets = 5;

    case Juniors = 6;

    case Under23 = 7;

    case Seniors = 8;

    case Veterans = 9;
}
