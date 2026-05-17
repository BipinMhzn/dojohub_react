<?php

namespace App\Modules\Tournaments\Interfaces;

use App\Modules\Tournaments\Models\Player;
use App\Modules\Tournaments\Models\Tournament;
use App\Modules\Tournaments\Models\TournamentCategory;
use BaseSystem\Interfaces\BaseInterface;
use Illuminate\Support\Collection;

/**
 * Interface RegistrationInterface
 */
interface RegistrationInterface extends BaseInterface
{
    public function getTournamentById(string $tournamentId): Tournament;

    public function getCategoryById(string $categoryId): TournamentCategory;

    public function getCategoryRegistrations(TournamentCategory $category): Collection;

    public function getPlayerList(TournamentCategory $category): Collection;

    public function getClubList(): Collection;

    public function getRegisteringPlayerById(TournamentCategory $category, string $playerId, ?string $registrationId = null): Player;

    public function destroyTournamentRegistration(TournamentCategory $category, string $registrationId): true;
}
