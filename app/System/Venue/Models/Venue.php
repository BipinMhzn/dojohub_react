<?php

namespace App\System\Venue\Models;

use App\System\Address\Models\Address;
use App\System\AppBaseModel;
use App\System\VenueMat\Models\VenueMat;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends AppBaseModel
{
    protected $fillable = [
        'name',
        'address_id',
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

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id', 'id');
    }

    public function mats(): HasMany
    {
        return $this->hasMany(VenueMat::class, 'venue_id', 'id');
    }
}
