<?php

namespace App\System\Address\Models;

use App\System\AppBaseModel;
use App\System\Country\Models\Country;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends AppBaseModel
{
    protected $fillable = [
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'latitude',
        'longitude',
    ];

    protected array $sortableColumns = [
        'city',
        'country_code',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }
}
