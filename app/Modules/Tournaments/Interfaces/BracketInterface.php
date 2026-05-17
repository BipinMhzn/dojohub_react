<?php

namespace App\Modules\Tournaments\Interfaces;

use App\Modules\Tournaments\Models\TournamentBracket;
use BaseSystem\Interfaces\BaseInterface;

/**
 * Interface BracketInterface
 */
interface BracketInterface extends BaseInterface
{
    public function getBracketByCategory(string $categoryId): ?TournamentBracket;

    public function createBracket(string $categoryId, array $validated): TournamentBracket;
}
