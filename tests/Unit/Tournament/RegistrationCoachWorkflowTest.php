<?php

namespace Tests\Unit\Tournament;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\System\Club\Models\Club;
use App\System\Coach\Models\Coach;
use App\System\Player\Models\Player;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentRegistration;
use App\System\Tournament\Models\TournamentRegistrationCoach;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistrationCoachWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): RegistrationInterface
    {
        return app(RegistrationInterface::class);
    }

    private function makeRegistration(?Tournament $tournament = null): TournamentRegistration
    {
        $tournament ??= Tournament::factory()->create();
        $category = TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => 0,
            'age_category' => 8,
            'min_age' => 18,
            'max_age' => 34,
            'weight_category' => 0,
            'min_weight' => 60,
            'max_weight' => 90,
            'status' => CategoryStatusEnum::RegistrationOpen,
        ]);

        return TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => Player::factory()->create()->id,
            'player_age' => 25,
            'status' => RegistrationStatusEnum::Registered,
        ]);
    }

    public function test_a_coach_can_be_assigned_to_a_registration(): void
    {
        $registration = $this->makeRegistration();
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);
        $coach = Coach::factory()->create();

        $link = $this->repository()->assignCoach($category, $registration->id, $coach->id);

        $this->assertSame($coach->id, $link->coach_id);
        $this->assertDatabaseHas('tournament_registration_coaches', [
            'registration_id' => $registration->id,
            'coach_id' => $coach->id,
        ]);
    }

    public function test_assigning_the_same_coach_twice_is_rejected(): void
    {
        $registration = $this->makeRegistration();
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);
        $coach = Coach::factory()->create();

        $this->repository()->assignCoach($category, $registration->id, $coach->id);

        $this->expectException(ValidationException::class);

        $this->repository()->assignCoach($category, $registration->id, $coach->id);
    }

    public function test_a_coach_can_be_removed(): void
    {
        $registration = $this->makeRegistration();
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);
        $coach = Coach::factory()->create();

        $link = $this->repository()->assignCoach($category, $registration->id, $coach->id);
        $this->repository()->removeCoach($category, $registration->id, $coach->id);

        $this->assertSoftDeleted('tournament_registration_coaches', ['id' => $link->id]);
    }

    public function test_reassigning_a_removed_coach_restores_the_row(): void
    {
        $registration = $this->makeRegistration();
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);
        $coach = Coach::factory()->create();

        $first = $this->repository()->assignCoach($category, $registration->id, $coach->id);
        $this->repository()->removeCoach($category, $registration->id, $coach->id);
        $second = $this->repository()->assignCoach($category, $registration->id, $coach->id);

        $this->assertSame($first->id, $second->id, 'Re-assignment should restore the soft-deleted row, not duplicate it.');
        $this->assertSame(1, TournamentRegistrationCoach::where('registration_id', $registration->id)->count());
    }

    public function test_assigning_a_coach_blocked_when_tournament_cancelled(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Cancelled]);
        $registration = $this->makeRegistration($tournament);
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);
        $coach = Coach::factory()->create();

        $this->expectException(TournamentCancelledException::class);

        $this->repository()->assignCoach($category, $registration->id, $coach->id);
    }
}
