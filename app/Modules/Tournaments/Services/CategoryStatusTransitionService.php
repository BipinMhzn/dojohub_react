<?php

namespace App\Modules\Tournaments\Services;

use App\Modules\Tournaments\Exceptions\InvalidCategoryStatusTransitionException;
use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\CategoryStatusTransitionServiceInterface;
use App\Modules\Tournaments\Interfaces\TournamentStatusResolverInterface;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\MatchStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentBracket;
use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentMatch;
use App\System\Tournament\Models\TournamentMatchCompetitor;
use App\System\Tournament\Models\TournamentResult;
use Illuminate\Support\Facades\DB;

/**
 * Class CategoryStatusTransitionService
 */
final class CategoryStatusTransitionService implements CategoryStatusTransitionServiceInterface
{
    public function __construct(
        private readonly TournamentStatusResolverInterface $resolver,
    ) {}

    public function transitionTo(TournamentCategory $category, CategoryStatusEnum $target): void
    {
        DB::transaction(function () use ($category, $target): void {
            $tournament = $category->tournament;

            $this->assertTournamentNotCancelled($tournament);
            $this->assertValidTransition($category->status, $target);

            $this->runSideEffects($category, $target);

            $category->update(['status' => $target]);

            $this->syncTournamentStatus($tournament);
        });
    }

    public function allowedTargets(CategoryStatusEnum $from): array
    {
        return match ($from) {
            CategoryStatusEnum::Draft => [
                CategoryStatusEnum::RegistrationOpen,
                CategoryStatusEnum::Cancelled,
            ],
            CategoryStatusEnum::RegistrationOpen => [
                CategoryStatusEnum::RegistrationClosed,
                CategoryStatusEnum::Cancelled,
            ],
            CategoryStatusEnum::RegistrationClosed => [
                CategoryStatusEnum::RegistrationOpen,
                CategoryStatusEnum::BracketGenerated,
                CategoryStatusEnum::Cancelled,
            ],
            CategoryStatusEnum::BracketGenerated => [
                CategoryStatusEnum::RegistrationClosed,
                CategoryStatusEnum::InProgress,
                CategoryStatusEnum::Cancelled,
            ],
            CategoryStatusEnum::InProgress => [
                CategoryStatusEnum::Completed,
                CategoryStatusEnum::Cancelled,
            ],
            CategoryStatusEnum::Completed, CategoryStatusEnum::Cancelled => [],
        };
    }

    private function assertTournamentNotCancelled(Tournament $tournament): void
    {
        if ($tournament->status === TournamentStatusEnum::Cancelled) {
            throw new TournamentCancelledException;
        }
    }

    private function assertValidTransition(CategoryStatusEnum $from, CategoryStatusEnum $target): void
    {
        if (! in_array($target, $this->allowedTargets($from), true)) {
            throw new InvalidCategoryStatusTransitionException($from, $target);
        }
    }

    private function runSideEffects(TournamentCategory $category, CategoryStatusEnum $target): void
    {
        if ($target === CategoryStatusEnum::RegistrationClosed) {
            if ($category->status === CategoryStatusEnum::BracketGenerated) {
                $this->resetBrackets($category);
            }

            $this->confirmRegistrations($category);
        }

        if ($target === CategoryStatusEnum::Completed) {
            $this->generateResults($category);
        }
    }

    private function confirmRegistrations(TournamentCategory $category): void
    {
        $category->registrations()
            ->where('status', RegistrationStatusEnum::Registered)
            ->update(['status' => RegistrationStatusEnum::Confirmed]);
    }

    /**
     * Regeneration reset (§7.5): soft-delete the bracket, its matches and competitors so a
     * fresh bracket can be generated. Children are removed first so FK RESTRICT is never tripped.
     */
    private function resetBrackets(TournamentCategory $category): void
    {
        $bracketIds = $category->brackets()->pluck('id');

        if ($bracketIds->isEmpty()) {
            return;
        }

        $matchIds = TournamentMatch::query()->whereIn('bracket_id', $bracketIds)->pluck('id');

        TournamentMatchCompetitor::query()->whereIn('match_id', $matchIds)->delete();
        TournamentMatch::query()->whereIn('id', $matchIds)->delete();
        TournamentBracket::query()->whereIn('id', $bracketIds)->delete();
    }

    /**
     * Result generation (§8.4): the terminal match (no winner_next_match_id) decides Gold + Silver;
     * the losers of the matches feeding into it take Bronze.
     */
    private function generateResults(TournamentCategory $category): void
    {
        $bracketIds = $category->brackets()->pluck('id');

        if ($bracketIds->isEmpty()) {
            return;
        }

        $final = TournamentMatch::query()
            ->whereIn('bracket_id', $bracketIds)
            ->whereNull('winner_next_match_id')
            ->where('status', MatchStatusEnum::Completed)
            ->with('competitors')
            ->orderByDesc('round_number')
            ->first();

        if (! $final || ! $final->winning_competitor_id) {
            return;
        }

        $positions = [];

        $gold = $final->competitors->firstWhere('id', $final->winning_competitor_id);
        $silver = $final->competitors->first(fn (TournamentMatchCompetitor $c): bool => $c->id !== $final->winning_competitor_id);

        if ($gold) {
            $positions[$gold->registration_id] = 1;
        }

        if ($silver) {
            $positions[$silver->registration_id] = 2;
        }

        $semifinals = TournamentMatch::query()
            ->whereIn('bracket_id', $bracketIds)
            ->where('winner_next_match_id', $final->id)
            ->where('status', MatchStatusEnum::Completed)
            ->with('competitors')
            ->get();

        foreach ($semifinals as $semifinal) {
            $loser = $semifinal->competitors->first(
                fn (TournamentMatchCompetitor $c): bool => $c->id !== $semifinal->winning_competitor_id,
            );

            if ($loser) {
                $positions[$loser->registration_id] = 3;
            }
        }

        foreach ($positions as $registrationId => $position) {
            TournamentResult::query()->updateOrCreate(
                ['registration_id' => $registrationId],
                ['position' => $position],
            );
        }
    }

    private function syncTournamentStatus(Tournament $tournament): void
    {
        if ($tournament->status === TournamentStatusEnum::Cancelled) {
            return;
        }

        $resolved = $this->resolver->resolve($tournament);

        if ($tournament->status !== $resolved) {
            $tournament->update(['status' => $resolved]);
        }
    }
}
