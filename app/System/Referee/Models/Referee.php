<?php

namespace App\System\Referee\Models;

use App\System\AppBaseModel;
use App\System\Country\Models\Country;
use App\System\Referee\Foundation\Enums\RefereeCertificationLevelEnum;
use App\System\User\Models\User;
use Database\Factories\RefereeFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referee extends AppBaseModel
{
    protected $fillable = [
        'user_id',
        'full_name',
        'nationality_code',
        'certification_level',
    ];

    protected string $defaultSortColumn = 'full_name';

    protected array $sortableColumns = [
        'full_name',
        'certification_level',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'certification_level' => RefereeCertificationLevelEnum::class,
        ];
    }

    protected static function newFactory(): RefereeFactory
    {
        return RefereeFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'nationality_code', 'code');
    }
}
