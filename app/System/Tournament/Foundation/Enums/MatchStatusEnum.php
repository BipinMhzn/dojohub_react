<?php

namespace App\System\Tournament\Foundation\Enums;

enum MatchStatusEnum: int
{
    case PENDING = 0;

    case ONGOING = 1;

    case COMPLETED = 2;

    case CANCELLED = 3;
}
