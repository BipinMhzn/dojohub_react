<?php

namespace Tests\Unit\Tournament;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): TournamentInterface
    {
        return app(TournamentInterface::class);
    }

    private function makeCategory(Tournament $tournament, CategoryStatusEnum $status, int $division = 0): TournamentCategory
    {
        return TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => 0,
            'age_category' => 0,
            'min_age' => 18,
            'max_age' => 34,
            'weight_category' => $division,
            'min_weight' => 60,
            'max_weight' => 73,
            'status' => $status,
        ]);
    }

    public function test_cancel_tournament_sets_status_to_cancelled(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Scheduled]);

        $this->repository()->cancelTournament($tournament->id);

        $this->assertSame(TournamentStatusEnum::Cancelled, $tournament->fresh()->status);
    }

    public function test_cancel_tournament_cancels_non_terminal_categories(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Scheduled]);
        $draft = $this->makeCategory($tournament, CategoryStatusEnum::Draft, 0);
        $open = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen, 1);
        $completed = $this->makeCategory($tournament, CategoryStatusEnum::Completed, 2);

        $this->repository()->cancelTournament($tournament->id);

        $this->assertSame(CategoryStatusEnum::Cancelled, $draft->fresh()->status);
        $this->assertSame(CategoryStatusEnum::Cancelled, $open->fresh()->status);
        $this->assertSame(CategoryStatusEnum::Completed, $completed->fresh()->status);
    }

    public function test_cancel_already_cancelled_tournament_throws(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Cancelled]);

        $this->expectException(TournamentCancelledException::class);

        $this->repository()->cancelTournament($tournament->id);
    }
}
