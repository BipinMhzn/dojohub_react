<?php

namespace App\Modules\Tournaments\Exceptions;

use Exception;

class TournamentCancelledException extends Exception
{
    public function __construct(string $message = 'No actions may be taken on a cancelled tournament.')
    {
        parent::__construct($message);
    }
}
