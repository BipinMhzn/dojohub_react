<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\Tournaments\Filters\TournamentsFilter;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Database\Models\Tournament;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class TournamentRepository
 * @package App\Modules\Tournaments\Repositories
 */
final class TournamentRepository extends BaseRepository implements TournamentInterface
{
    /**
     * TournamentRepository constructor.
     * @param Tournament $model
     */
    public function __construct(Tournament $model)
    {
        parent::__construct($model);
    }

    /**
     * @param Builder $builder
     * @return TournamentsFilter
     */
    public function getFilter(Builder $builder): TournamentsFilter
    {
        return new TournamentsFilter($builder);
    }

}
