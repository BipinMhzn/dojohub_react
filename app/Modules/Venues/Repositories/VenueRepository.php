<?php

namespace App\Modules\Venues\Repositories;

use App\Modules\Venues\Filters\VenuesFilter;
use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Models\Venue;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class VenueRepository
 */
final class VenueRepository extends BaseRepository implements VenueInterface
{
    /**
     * VenueRepository constructor.
     */
    public function __construct(Venue $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): VenuesFilter
    {
        return new VenuesFilter($builder);
    }
}
