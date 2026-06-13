<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\TournamentCategories\Models\TournamentCategory;
use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Filters\TournamentsFilter;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Models\Tournament;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Class TournamentRepository
 */
final class TournamentRepository extends BaseRepository implements TournamentInterface
{
    public function __construct(Tournament $model, private readonly TournamentCategory $category)
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

    /**
     * @throws TournamentCancelledException
     */
    public function cancelTournament(string $tournamentId): Tournament
    {
        return DB::transaction(function () use ($tournamentId): Tournament {
            $tournament = $this->model->newQuery()->lockForUpdate()->findOrFail($tournamentId);

            if ($tournament->status === TournamentStatusEnum::Cancelled) {
                throw new TournamentCancelledException('Tournament is already cancelled.');
            }

            $this->category->newQuery()
                ->where('tournament_id', $tournament->id)
                ->whereNotIn('status', [
                    CategoryStatusEnum::Completed->value,
                    CategoryStatusEnum::Cancelled->value,
                ])
                ->update(['status' => CategoryStatusEnum::Cancelled]);

            $tournament->update(['status' => TournamentStatusEnum::Cancelled]);

            return $tournament;
        });
    }
}
