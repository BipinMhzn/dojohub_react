<?php

namespace App\System\BeltRank\Models;

use App\System\BeltRank\Foundation\Enums\BeltRankKindEnum;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class BeltRank extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'kind',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'kind' => BeltRankKindEnum::class,
            'sort_order' => 'integer',
        ];
    }
}
