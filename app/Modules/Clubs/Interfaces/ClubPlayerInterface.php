<?php

namespace App\Modules\Clubs\Interfaces;

use App\Modules\Clubs\Models\ClubPlayer;
use BaseSystem\Interfaces\BaseInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ClubPlayerInterface extends BaseInterface
{
    /** Paginated list of all memberships (active + historical) for the given club. */
    public function getClubPlayers(string $clubId, array $filters): LengthAwarePaginator;

    /** All memberships for the given club, unpaginated, ordered active-first then by name. */
    public function getAllClubPlayers(string $clubId): Collection;

    /** All players NOT currently active members of the club — for the assign dropdown. */
    public function getAvailablePlayers(string $clubId): Collection;

    /** Create a new membership record after checking for duplicate active memberships. */
    public function assignPlayer(string $clubId, array $validated): ClubPlayer;

    /** Set ended_at on the membership to end it. */
    public function endMembership(string $clubId, string $membershipId, array $validated): ClubPlayer;

    /** Update belt_rank and grade on a membership. */
    public function updateMembershipDetails(string $clubId, string $membershipId, array $validated): ClubPlayer;
}
