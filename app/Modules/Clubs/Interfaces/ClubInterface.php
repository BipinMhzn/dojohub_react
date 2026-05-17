<?php

namespace App\Modules\Clubs\Interfaces;

use App\Modules\Clubs\Models\Club;
use BaseSystem\Interfaces\BaseInterface;

/**
 * Interface ClubInterface
 * @package App\Modules\Clubs\Interfaces
 */
interface ClubInterface extends BaseInterface
{
    public function createClub(array $validated): Club;

    public function updateClub(array $validated, string $clubId): Club;

    public function deleteClub(string $clubId): true;
}
