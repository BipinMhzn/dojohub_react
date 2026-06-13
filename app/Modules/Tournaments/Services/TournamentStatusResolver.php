<?php

namespace App\Modules\Tournaments\Services;

use App\Modules\Tournaments\Interfaces\TournamentStatusResolverInterface;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentCategory;
use Illuminate\Support\Collection;

/**
 * Class TournamentStatusResolver
 */
final class TournamentStatusResolver implements TournamentStatusResolverInterface
{
    public function resolve(Tournament $tournament): TournamentStatusEnum
    {
        if ($tournament->status === TournamentStatusEnum::Cancelled) {
            return TournamentStatusEnum::Cancelled;
        }

        /** @var Collection<int, CategoryStatusEnum> $statuses */
        $statuses = $tournament->categories()
            ->where('status', '!=', CategoryStatusEnum::Cancelled)
            ->get()
            ->map(fn (TournamentCategory $category): CategoryStatusEnum => $category->status);

        if ($statuses->isEmpty()) {
            return TournamentStatusEnum::Scheduled;
        }

        $isLive = static fn (CategoryStatusEnum $status): bool => in_array(
            $status,
            [CategoryStatusEnum::BracketGenerated, CategoryStatusEnum::InProgress],
            true,
        );

        if ($statuses->contains($isLive)) {
            return TournamentStatusEnum::InProgress;
        }

        if ($statuses->every(fn (CategoryStatusEnum $status): bool => $status === CategoryStatusEnum::Completed)) {
            return TournamentStatusEnum::Completed;
        }

        return TournamentStatusEnum::Scheduled;
    }
}
