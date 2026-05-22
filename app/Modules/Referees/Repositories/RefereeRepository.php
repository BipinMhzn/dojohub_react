<?php

namespace App\Modules\Referees\Repositories;

use App\Modules\Referees\Filters\RefereesFilter;
use App\Modules\Referees\Interfaces\RefereeInterface;
use App\Modules\Referees\Models\Referee;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

final class RefereeRepository extends BaseRepository implements RefereeInterface
{
    public function __construct(Referee $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): RefereesFilter
    {
        return new RefereesFilter($builder);
    }

    public function onBeforeResult(Builder $builder): void
    {
        $builder->with('country', 'user');
    }

    public function getById(string $id, array $attributes = []): Referee
    {
        return $this->model->newQuery()
            ->with('country', 'user')
            ->findOrFail($id);
    }
}
