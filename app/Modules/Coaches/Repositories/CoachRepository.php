<?php

namespace App\Modules\Coaches\Repositories;

use App\Modules\Coaches\Filters\CoachesFilter;
use App\Modules\Coaches\Interfaces\CoachInterface;
use App\Modules\Coaches\Models\Coach;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

final class CoachRepository extends BaseRepository implements CoachInterface
{
    public function __construct(Coach $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): CoachesFilter
    {
        return new CoachesFilter($builder);
    }

    public function onBeforeResult(Builder $builder): void
    {
        $builder->with('user', 'activeMemberships.club');
    }

    public function getById(string $id, array $attributes = []): Coach
    {
        return $this->model->newQuery()
            ->with('user', 'activeMemberships.club')
            ->findOrFail($id);
    }
}
