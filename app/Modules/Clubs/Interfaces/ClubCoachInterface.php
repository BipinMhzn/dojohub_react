<?php

namespace App\Modules\Clubs\Interfaces;

use App\Modules\Clubs\Models\ClubCoach;
use BaseSystem\Interfaces\BaseInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ClubCoachInterface extends BaseInterface
{
    /** Paginated list of all coach memberships (active + historical) for the given club. */
    public function getClubCoaches(string $clubId, array $filters): LengthAwarePaginator;

    /** All coaches NOT currently active members of the club — for the assign dropdown. */
    public function getAvailableCoaches(string $clubId): Collection;

    /** Create a new membership record after checking for duplicate active memberships. */
    public function assignCoach(string $clubId, array $validated): ClubCoach;

    /** Set ended_at on the membership to end it. */
    public function endMembership(string $clubId, string $membershipId, array $validated): ClubCoach;
}
