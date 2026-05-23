<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\Tournaments\Filters\TournamentsFilter;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Models\Tournament;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class TournamentRepository
 */
final class TournamentRepository extends BaseRepository implements TournamentInterface
{
    /**
     * TournamentRepository constructor.
     */
    public function __construct(Tournament $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): TournamentsFilter
    {
        return new TournamentsFilter($builder);
    }

    public function onBeforeResult(Builder $builder): void
    {
        $builder->with('ruleSet');
    }

    public function getById(string $id, array $attributes = []): Tournament
    {
        return $this->model->newQuery()->with('ruleSet')->findOrFail($id);
    }
}
