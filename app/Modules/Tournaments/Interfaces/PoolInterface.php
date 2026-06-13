<?php

namespace App\Modules\Tournaments\Interfaces;

use App\Modules\Tournaments\Models\TournamentPoolCompetitor;
use App\System\Tournament\Models\TournamentCategory;
use BaseSystem\Interfaces\BaseInterface;
use Illuminate\Support\Collection;

/**
 * Interface PoolInterface
 */
interface PoolInterface extends BaseInterface
{
    public function getPoolsForCategory(TournamentCategory $category): Collection;

    public function getConfirmedRegistrations(TournamentCategory $category): Collection;

    public function assignCompetitor(TournamentCategory $category, string $poolId, string $registrationId): TournamentPoolCompetitor;

    public function removeCompetitor(TournamentCategory $category, string $poolCompetitorId): true;
}
