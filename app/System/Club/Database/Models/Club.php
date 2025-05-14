<?php

namespace App\System\Club\Database\Models;

use App\System\AppBaseModel;
use App\System\Foundation\Traits\AvatarGenerator;
use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Club extends AppBaseModel
{
    use AvatarGenerator;

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

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (is_null($value)) {
                    return $this->generateAvatar($attributes['name']);
                }
                return $value;
            }
        );
    }
}
