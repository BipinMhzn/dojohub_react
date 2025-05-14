<?php

namespace App\System\Player\Database\Models;

use App\System\AppBaseModel;
use App\System\Foundation\Traits\AvatarGenerator;
use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Player extends AppBaseModel
{
    use AvatarGenerator;

    protected $fillable = [
        'name',
        'gender',
        'dob',
        'weight',
        'profile_photo_url',
        'phone_number',
    ];

    protected array $sortableColumns = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'gender' => PlayerGenderEnum::class,
    ];

    protected static function newFactory(): PlayerFactory
    {
        return PlayerFactory::new();
    }

    protected function profilePhotoUrl(): Attribute
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
