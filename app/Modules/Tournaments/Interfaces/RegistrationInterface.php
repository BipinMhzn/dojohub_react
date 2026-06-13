<?php

namespace App\Modules\Tournaments\Interfaces;

use App\Modules\Tournaments\Models\Player;
use App\Modules\Tournaments\Models\Tournament;
use App\Modules\Tournaments\Models\TournamentRegistrationCoach;
use App\Modules\Tournaments\Models\TournamentRegistrationPayment;
use App\System\Tournament\Models\TournamentCategory;
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

    public function getRegisteringPlayerById(TournamentCategory $category, string $playerId, ?string $clubId = null, ?string $registrationId = null): Player;

    public function destroyTournamentRegistration(TournamentCategory $category, string $registrationId): true;

    public function withdrawRegistration(TournamentCategory $category, string $registrationId): true;

    public function getCoachList(): Collection;

    public function assignCoach(TournamentCategory $category, string $registrationId, string $coachId): TournamentRegistrationCoach;

    public function removeCoach(TournamentCategory $category, string $registrationId, string $coachId): true;

    public function recordPayment(TournamentCategory $category, string $registrationId, array $validated): TournamentRegistrationPayment;

    public function deletePayment(TournamentCategory $category, string $registrationId, string $paymentId): true;
}
