<?php

namespace App\Modules\Players\Repositories;

use App\Modules\Players\Filters\PlayersFilter;
use App\Modules\Players\Interfaces\PlayerInterface;
use App\Modules\Players\Models\Player;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

final class PlayerRepository extends BaseRepository implements PlayerInterface
{
    public function __construct(Player $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): PlayersFilter
    {
        return new PlayersFilter($builder);
    }

    public function onBeforeResult(Builder $builder): void
    {
        $builder->with('country', 'currentBeltRank');
    }

    public function getById(string $id, array $attributes = []): Player
    {
        return $this->model->newQuery()
            ->with('country', 'currentBeltRank', 'address.country')
            ->findOrFail($id);
    }
}
