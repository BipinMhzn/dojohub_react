<?php

namespace App\System\Tournament\Foundation\Enums;

enum SeedingMethodEnum: int
{
    case Random = 0;

    case Manual = 1;

    case ByRank = 2;
}
