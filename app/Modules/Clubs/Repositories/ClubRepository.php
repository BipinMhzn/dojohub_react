<?php

namespace App\Modules\Clubs\Repositories;

use App\Modules\Clubs\Filters\ClubsFilter;
use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Database\Models\Club;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ClubRepository
 * @package App\Modules\Clubs\Repositories
 */
final class ClubRepository extends BaseRepository implements ClubInterface
{
    /**
     * ClubRepository constructor.
     * @param Club $model
     */
    public function __construct(Club $model)
    {
        parent::__construct($model);
    }

    /**
     * @param Builder $builder
     * @return ClubsFilter
     */
    public function getFilter(Builder $builder): ClubsFilter
    {
        return new ClubsFilter($builder);
    }

}
