<?php

namespace App\Modules\Clubs\Repositories;

use App\Modules\Clubs\Filters\ClubsFilter;
use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Models\Club;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ClubRepository
 */
final class ClubRepository extends BaseRepository implements ClubInterface
{
    /**
     * ClubRepository constructor.
     */
    public function __construct(Club $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): ClubsFilter
    {
        return new ClubsFilter($builder);
    }
}
