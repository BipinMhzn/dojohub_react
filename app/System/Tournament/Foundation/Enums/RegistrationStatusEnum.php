<?php

namespace App\System\Tournament\Foundation\Enums;

enum RegistrationStatusEnum: int
{
    case Registered = 0;

    case Confirmed = 1;

    case Withdrawn = 2;
}
