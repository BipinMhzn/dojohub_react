<?php

namespace App\Modules\Players\Repositories;

use App\Modules\Players\Filters\PlayersFilter;
use App\Modules\Players\Interfaces\PlayerInterface;
use App\Modules\Players\Models\Player;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class PlayerRepository
 */
final class PlayerRepository extends BaseRepository implements PlayerInterface
{
    /**
     * PlayerRepository constructor.
     */
    public function __construct(Player $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): PlayersFilter
    {
        return new PlayersFilter($builder);
    }
}
