<?php

namespace App\System\Venue\Models;

use App\System\Address\Models\Address;
use App\System\AppBaseModel;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venue extends AppBaseModel
{
    protected $fillable = [
        'name',
        'address_id',
        'mat_count',
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
}
