<?php

namespace App\Modules\Tournaments\Exceptions;

use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use Exception;

class InvalidCategoryStatusTransitionException extends Exception
{
    public function __construct(CategoryStatusEnum $from, CategoryStatusEnum $to)
    {
        parent::__construct("Cannot transition category from {$from->name} to {$to->name}.");
    }
}
