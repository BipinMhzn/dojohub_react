<?php

namespace App\Modules\Players\Repositories;

use App\Modules\Players\Filters\PlayersFilter;
use App\Modules\Players\Interfaces\PlayerInterface;
use App\Modules\Players\Database\Models\Player;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class PlayerRepository
 * @package App\Modules\Players\Repositories
 */
final class PlayerRepository extends BaseRepository implements PlayerInterface
{
    /**
     * PlayerRepository constructor.
     * @param Player $model
     */
    public function __construct(Player $model)
    {
        parent::__construct($model);
    }

    /**
     * @param Builder $builder
     * @return PlayersFilter
     */
    public function getFilter(Builder $builder): PlayersFilter
    {
        return new PlayersFilter($builder);
    }

}
