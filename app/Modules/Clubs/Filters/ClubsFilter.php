<?php

namespace App\Modules\Clubs\Filters;

use BaseSystem\Filters\BaseFilter;

/**
 * Class ClubsController
 * @package App\Modules\Clubs\Filters
 */
final class ClubsFilter extends BaseFilter
{
    /**
     * To do filter here
     * @param string $keyword
     */
    public function keyword(string $keyword = ''): void
    {
       $this->name($keyword);
    }

}
