<?php

namespace App\Modules\Tournaments\Exceptions;

use Exception;

class WeighInNotAllowedException extends Exception
{
    public function __construct(string $message = 'Weigh-ins are only allowed once registration is closed.')
    {
        parent::__construct($message);
    }
}
