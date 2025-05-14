<?php

namespace App\System\Tournament\Foundation\Enums;

enum TournamentStatusEnum: int
{
    case SCHEDULED = 0;
    case ONGOING = 1;
    case COMPLETED = 2;
    case CANCELLED = 3;
}
