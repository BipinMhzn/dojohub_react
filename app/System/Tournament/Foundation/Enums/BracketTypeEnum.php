<?php

namespace App\System\Tournament\Foundation\Enums;

enum BracketTypeEnum: int
{
    case SingleElimination = 0;

    case DoubleElimination = 1;

    case PoolFinal = 2;

    case Repechage = 3;
}
