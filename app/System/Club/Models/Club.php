<?php

namespace App\System\Club\Models;

use App\System\Address\Models\Address;
use App\System\AppBaseModel;
use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Club extends AppBaseModel
{
    protected $fillable = [
        'name',
        'abbreviation',
        'logo_url',
        'address_id',
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

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id', 'id');
    }
}
