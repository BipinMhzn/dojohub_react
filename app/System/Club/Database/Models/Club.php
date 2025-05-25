<?php

namespace App\System\Club\Database\Models;

use App\System\AppBaseModel;
use Database\Factories\ClubFactory;

class Club extends AppBaseModel
{
    protected $fillable = [
        'name',
        'abbreviation',
        'logo_url',
        'address',
        'phone_number',
    ];

    protected array $sortableColumns = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): ClubFactory
    {
        return ClubFactory::new();
    }
}
