<?php

namespace App\Modules\Coaches\Filters;

use BaseSystem\Filters\BaseFilter;

/**
 * Class CoachesFilter
 */
final class CoachesFilter extends BaseFilter
{
    public function keyword(string $keyword = ''): void
    {
        $this->builder->where('full_name', 'like', "%{$keyword}%");
    }
}
