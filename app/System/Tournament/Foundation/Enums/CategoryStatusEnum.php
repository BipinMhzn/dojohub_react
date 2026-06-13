<?php

namespace App\System\Tournament\Foundation\Enums;

enum CategoryStatusEnum: int
{
    case Draft = 0;

    case RegistrationOpen = 1;

    case RegistrationClosed = 2;

    case BracketGenerated = 3;

    case InProgress = 4;

    case Completed = 5;

    case Cancelled = 6;
}
