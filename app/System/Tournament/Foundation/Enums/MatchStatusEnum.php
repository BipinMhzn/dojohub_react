<?php

namespace App\System\Tournament\Foundation\Enums;

enum MatchStatusEnum: int
{
    case Pending = 0;

    case Active = 1;

    case Completed = 2;
}
