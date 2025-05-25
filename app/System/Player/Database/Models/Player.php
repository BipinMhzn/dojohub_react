<?php

namespace App\System\Player\Database\Models;

use App\System\AppBaseModel;
use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use Database\Factories\PlayerFactory;

class Player extends AppBaseModel
{
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
}
