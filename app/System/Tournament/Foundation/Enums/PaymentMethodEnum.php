<?php

namespace App\System\Tournament\Foundation\Enums;

enum PaymentMethodEnum: int
{
    case Cash = 0;

    case Bank = 1;

    case Online = 2;
}
