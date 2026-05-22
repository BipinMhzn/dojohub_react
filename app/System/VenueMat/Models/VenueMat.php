<?php

namespace App\System\VenueMat\Models;

use App\System\AppBaseModel;
use App\System\Venue\Models\Venue;
use Database\Factories\VenueMatFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueMat extends AppBaseModel
{
    protected $table = 'venue_mats';

    protected $fillable = [
        'venue_id',
        'mat_number',
        'display_name',
    ];

    protected function casts(): array
    {
        return [
            'mat_number' => 'integer',
        ];
    }

    protected static function newFactory(): VenueMatFactory
    {
        return VenueMatFactory::new();
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id', 'id');
    }
}
