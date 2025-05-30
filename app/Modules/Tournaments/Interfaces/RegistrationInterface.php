<?php

namespace App\Modules\Tournaments\Interfaces;

use App\Modules\Tournaments\Database\Models\Player;
use App\Modules\Tournaments\Database\Models\Tournament;
use BaseSystem\Interfaces\BaseInterface;
use Illuminate\Support\Collection;

/**
 * Interface TournamentInterface
 * @package App\Modules\Tournaments\Interfaces
 */
interface RegistrationInterface extends BaseInterface
{

    public function getTournamentById(string $tournamentId): Tournament;

    public function getScheduledTournamentById(string $tournamentId): Tournament;

    public function getTournamentRegistrations(Tournament $tournament): Collection;

    public function getPlayerList(Tournament $tournament): Collection;

    public function getClubList(): Collection;

    public function getRegisteringPlayerById(Tournament $tournament, string $playerId, ?string $registrationId = null): Player;

    public function updateTournamentRegistration(Tournament $tournament, string $registrationId, array $inputs): Tournament;

    public function destroyTournamentRegistration(Tournament $tournament, string $registrationId): true;
}
