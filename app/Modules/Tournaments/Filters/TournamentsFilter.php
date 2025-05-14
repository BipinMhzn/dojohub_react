<?php

namespace App\Modules\Tournaments\Filters;

use BaseSystem\Filters\BaseFilter;

/**
 * Class TournamentsController
 * @package App\Modules\Tournaments\Filters
 */
final class TournamentsFilter extends BaseFilter
{
    /**
     * To do filter here
     * @param string $keyword
     */
    public function keyword(string $keyword = ''): void
    {
       $this->title($keyword);
    }

}
