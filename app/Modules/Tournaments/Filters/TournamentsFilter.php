<?php

namespace App\Modules\Tournaments\Filters;

use BaseSystem\Filters\BaseFilter;

/**
 * Class TournamentsController
 */
final class TournamentsFilter extends BaseFilter
{
    /**
     * To do filter here
     */
    public function keyword(string $keyword = ''): void
    {
        $this->name($keyword);
    }
}
