<?php

namespace App\Modules\Players\Filters;

use BaseSystem\Filters\BaseFilter;

/**
 * Class PlayersController
 * @package App\Modules\Players\Filters
 */
final class PlayersFilter extends BaseFilter
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
