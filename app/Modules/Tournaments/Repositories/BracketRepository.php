<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\Tournaments\Interfaces\BracketInterface;
use App\Modules\Tournaments\Models\TournamentBracket;
use App\System\Tournament\Foundation\Enums\BracketStatusEnum;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class BracketRepository
 */
final class BracketRepository extends BaseRepository implements BracketInterface
{
    public function __construct(TournamentBracket $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): void {}

    public function getBracketByCategory(string $categoryId): ?TournamentBracket
    {
        return $this->model->newQuery()
            ->where('tournament_category_id', $categoryId)
            ->first();
    }

    public function createBracket(string $categoryId, array $validated): TournamentBracket
    {
        $validated['tournament_category_id'] = $categoryId;
        $validated['status'] = BracketStatusEnum::Pending;

        return $this->model->newQuery()->create($validated);
    }
}
