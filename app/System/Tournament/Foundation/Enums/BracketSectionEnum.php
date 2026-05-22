<?php

namespace App\System\Tournament\Foundation\Enums;

enum BracketSectionEnum: int
{
    case Main = 0;

    case Repechage = 1;

    case Bronze = 2;

    case GoldFinal = 3;
}
