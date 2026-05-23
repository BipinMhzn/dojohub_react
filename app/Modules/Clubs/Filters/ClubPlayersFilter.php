<?php

namespace App\Modules\Clubs\Filters;

use BaseSystem\Filters\BaseFilter;

final class ClubPlayersFilter extends BaseFilter
{
    public function keyword(string $keyword = ''): void
    {
        if ($keyword !== '') {
            $this->builder->whereHas('player', fn ($q) => $q->where('name', 'like', "%{$keyword}%"));
        }
    }

    public function status(string $status = ''): void
    {
        match ($status) {
            'active' => $this->builder->whereNull('ended_at'),
            'ended' => $this->builder->whereNotNull('ended_at'),
            default => null,
        };
    }
}
