<?php

namespace App\System\Tournament\Foundation\Enums;

enum TournamentStatusEnum: int
{
    case Scheduled = 0;

    case InProgress = 1;

    case Completed = 2;

    case Cancelled = 3;
}
