<?php

namespace App\Modules\Venues\Repositories;

use App\Modules\Venues\Filters\VenuesFilter;
use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Database\Models\Venue;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class VenueRepository
 * @package App\Modules\Venues\Repositories
 */
final class VenueRepository extends BaseRepository implements VenueInterface
{
    /**
     * VenueRepository constructor.
     * @param Venue $model
     */
    public function __construct(Venue $model)
    {
        parent::__construct($model);
    }

    /**
     * @param Builder $builder
     * @return VenuesFilter
     */
    public function getFilter(Builder $builder): VenuesFilter
    {
        return new VenuesFilter($builder);
    }

}
