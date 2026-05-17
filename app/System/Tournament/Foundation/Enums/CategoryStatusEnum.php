<?php

namespace App\System\Tournament\Foundation\Enums;

enum CategoryStatusEnum: int
{
    case Pending = 0;

    case Seeding = 1;

    case Active = 2;

    case Completed = 3;
}
