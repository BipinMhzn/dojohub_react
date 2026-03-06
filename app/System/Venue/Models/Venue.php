<?php

namespace App\System\Venue\Models;

use App\System\AppBaseModel;
use Database\Factories\VenueFactory;

class Venue extends AppBaseModel
{
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'country',
    ];

    protected array $sortableColumns = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): VenueFactory
    {
        return VenueFactory::new();
    }
}
