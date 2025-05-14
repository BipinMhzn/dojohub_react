<?php

namespace App\Modules\Users\Filters;

use BaseSystem\Filters\BaseFilter;

/**
 * Class UsersController
 * @package App\Modules\Users\Filters
 */
final class UsersFilter extends BaseFilter
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
