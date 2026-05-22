<?php

namespace App\Modules\Venues\Interfaces;

use App\Modules\Venues\Models\VenueMat;
use BaseSystem\Interfaces\BaseInterface;

interface VenueMatInterface extends BaseInterface
{
    public function createMat(string $venueId, array $validated): VenueMat;

    public function updateMat(string $matId, array $validated): VenueMat;

    public function deleteMat(string $matId): true;
}
