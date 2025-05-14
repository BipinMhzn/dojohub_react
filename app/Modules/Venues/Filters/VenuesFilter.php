<?php

namespace App\Modules\Venues\Filters;

use BaseSystem\Filters\BaseFilter;

/**
 * Class VenuesController
 * @package App\Modules\Venues\Filters
 */
final class VenuesFilter extends BaseFilter
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
