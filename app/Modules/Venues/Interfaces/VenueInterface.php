<?php

namespace App\Modules\Venues\Interfaces;

use App\Modules\Venues\Models\Venue;
use BaseSystem\Interfaces\BaseInterface;

/**
 * Interface VenueInterface
 * @package App\Modules\Venues\Interfaces
 */
interface VenueInterface extends BaseInterface
{

    public function createVenue(array $validated): Venue;

    public function updateVenue(array $validated, string $venueId): Venue;

    public function deleteVenue(string $venueId): true;
}
