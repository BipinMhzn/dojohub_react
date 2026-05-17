<?php

namespace App\System\Address\Models;

use App\System\AppBaseModel;
use Database\Factories\AddressFactory;

class Address extends AppBaseModel
{
    protected $fillable = [
        'address',
        'city',
        'state',
        'country',
    ];

    protected array $sortableColumns = [
        'city',
        'country',
        'created_at',
    ];

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }
}
