<?php

namespace App\Modules\Referees\Filters;

use BaseSystem\Filters\BaseFilter;

/**
 * Class RefereesController
 */
final class RefereesFilter extends BaseFilter
{
    /**
     * To do filter here
     */
    public function keyword(string $keyword = ''): void
    {
        $this->builder->where('full_name', 'like', "%{$keyword}%");
    }
}
