<?php

namespace Tests\Unit\Tournament;

use App\Modules\Tournaments\Exceptions\PlayerAlreadyRegisteredException;
use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\System\Club\Models\Club;
use App\System\Player\Models\Player;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentRegistration;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): RegistrationInterface
    {
        return app(RegistrationInterface::class);
    }

    private function makeCategory(Tournament $tournament, CategoryStatusEnum $status, int $division = 0): TournamentCategory
    {
        return TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => 0,
            'age_category' => 8,
            'min_age' => 18,
            'max_age' => 34,
            'weight_category' => $division,
            'min_weight' => 60,
            'max_weight' => 90,
            'status' => $status,
        ]);
    }

    private function makeRegistration(TournamentCategory $category, RegistrationStatusEnum $status): TournamentRegistration
    {
        return TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => Player::factory()->create()->id,
            'player_age' => 25,
            'status' => $status,
        ]);
    }

    private function makeEligiblePlayer(): Player
    {
        return Player::factory()->create([
            'gender' => 0,
            'dob' => Carbon::now()->subYears(25)->toDateString(),
            'weight' => 75,
        ]);
    }

    public function test_eligible_player_resolves_for_registration(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen);
        $player = $this->makeEligiblePlayer();

        $resolved = $this->repository()->getRegisteringPlayerById($category, $player->id);

        $this->assertSame($player->id, $resolved->id);
    }

    public function test_player_with_mismatched_gender_is_rejected(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen);
        $player = $this->makeEligiblePlayer();
        $player->update(['gender' => 1]);

        $this->expectException(ModelNotFoundException::class);

        $this->repository()->getRegisteringPlayerById($category, $player->id);
    }

    public function test_player_outside_age_range_is_rejected(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen);
        $player = $this->makeEligiblePlayer();
        $player->update(['dob' => Carbon::now()->subYears(12)->toDateString()]); // younger than min_age 18

        $this->expectException(ModelNotFoundException::class);

        $this->repository()->getRegisteringPlayerById($category, $player->id);
    }

    public function test_player_outside_weight_range_is_rejected(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen);
        $player = $this->makeEligiblePlayer();
        $player->update(['weight' => 120]); // above max_weight 90

        $this->expectException(ModelNotFoundException::class);

        $this->repository()->getRegisteringPlayerById($category, $player->id);
    }

    public function test_duplicate_registration_is_rejected(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen);
        $player = $this->makeEligiblePlayer();

        TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => $player->id,
            'player_age' => 25,
            'status' => RegistrationStatusEnum::Registered,
        ]);

        $this->expectException(PlayerAlreadyRegisteredException::class);

        $this->repository()->getRegisteringPlayerById($category, $player->id);
    }

    public function test_club_not_belonging_to_player_is_rejected(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen);
        $player = $this->makeEligiblePlayer(); // no club memberships
        $club = Club::factory()->create();

        $this->expectException(ValidationException::class);

        $this->repository()->getRegisteringPlayerById($category, $player->id, $club->id);
    }

    public function test_withdraw_sets_status_and_soft_deletes(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen);
        $registration = $this->makeRegistration($category, RegistrationStatusEnum::Registered);

        $category->load('tournament');
        $this->repository()->withdrawRegistration($category, $registration->id);

        $this->assertSoftDeleted('tournament_registrations', ['id' => $registration->id]);

        $freshWithTrashed = TournamentRegistration::withTrashed()->find($registration->id);
        $this->assertSame(RegistrationStatusEnum::Withdrawn, $freshWithTrashed->status);
    }

    public function test_withdraw_blocked_when_tournament_cancelled(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Cancelled]);
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen);
        $registration = $this->makeRegistration($category, RegistrationStatusEnum::Registered);

        $category->load('tournament');

        $this->expectException(TournamentCancelledException::class);

        $this->repository()->withdrawRegistration($category, $registration->id);
    }

    public function test_destroy_blocked_when_tournament_cancelled(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Cancelled]);
        $category = $this->makeCategory($tournament, CategoryStatusEnum::RegistrationOpen);
        $registration = $this->makeRegistration($category, RegistrationStatusEnum::Registered);

        $category->load('tournament');

        $this->expectException(TournamentCancelledException::class);

        $this->repository()->destroyTournamentRegistration($category, $registration->id);
    }
}
