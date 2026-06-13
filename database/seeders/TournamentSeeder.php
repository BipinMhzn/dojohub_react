<?php

namespace Database\Seeders;

use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Interfaces\CategoryStatusTransitionServiceInterface;
use App\System\Club\Models\Club;
use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use App\System\Player\Models\Player;
use App\System\Tournament\Foundation\Enums\AgeCategoryEnum;
use App\System\Tournament\Foundation\Enums\BracketStatusEnum;
use App\System\Tournament\Foundation\Enums\BracketTypeEnum;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\SeedingMethodEnum;
use App\System\Tournament\Foundation\Enums\TournamentLevelEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Foundation\Enums\WeightCategoryEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentBracket;
use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentRegistration;
use App\System\TournamentRuleSet\Models\TournamentRuleSet;
use App\System\Venue\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TournamentSeeder extends Seeder
{
    public function run(): void
    {
        $ruleSet = TournamentRuleSet::first();
        $venue = Venue::first() ?? Venue::factory()->create();

        $clubs = Club::factory()->withoutAddress()->count(6)->create();

        // ── Tournament 1: Scheduled — registration open for some categories ──

        $t1 = Tournament::create([
            'name' => 'Kathmandu City Open 2026',
            'level' => TournamentLevelEnum::Regional,
            'status' => TournamentStatusEnum::Scheduled,
            'start_at' => Carbon::now()->addMonths(2),
            'end_at' => Carbon::now()->addMonths(2)->addDays(2),
            'venue_id' => $venue->id,
            'rule_set_id' => $ruleSet?->id,
            'timezone' => 'Asia/Kathmandu',
        ]);

        // Draft category
        TournamentCategory::create([
            'tournament_id' => $t1->id,
            'gender' => PlayerGenderEnum::Female,
            'age_category' => AgeCategoryEnum::Seniors,
            'min_age' => 18,
            'max_age' => 35,
            'weight_category' => WeightCategoryEnum::Female57,
            'min_weight' => 52.01,
            'max_weight' => 57.00,
            'status' => CategoryStatusEnum::Draft,
            'registration_open_at' => Carbon::now()->addWeek(),
        ]);

        // RegistrationOpen category with players
        $maleSeniorsCategory = TournamentCategory::create([
            'tournament_id' => $t1->id,
            'gender' => PlayerGenderEnum::Male,
            'age_category' => AgeCategoryEnum::Seniors,
            'min_age' => 18,
            'max_age' => 35,
            'weight_category' => WeightCategoryEnum::Male73,
            'min_weight' => 66.01,
            'max_weight' => 73.00,
            'status' => CategoryStatusEnum::RegistrationOpen,
            'registration_open_at' => Carbon::now()->subDay(),
            'registration_close_at' => Carbon::now()->addMonth(),
        ]);

        $malePlayers = $this->createPlayers(PlayerGenderEnum::Male, 18, 34, 67, 73, 6, $clubs);
        foreach ($malePlayers->take(4) as $player) {
            TournamentRegistration::create([
                'tournament_category_id' => $maleSeniorsCategory->id,
                'club_id' => $clubs->random()->id,
                'player_id' => $player->id,
                'player_age' => Carbon::parse($player->dob)->age,
                'status' => RegistrationStatusEnum::Registered,
            ]);
        }

        // ── Tournament 2: InProgress — bracket generated, ready to compete ──

        $t2 = Tournament::create([
            'name' => 'National Judo Championship 2026',
            'level' => TournamentLevelEnum::National,
            'status' => TournamentStatusEnum::Scheduled,
            'start_at' => Carbon::now()->subDay(),
            'end_at' => Carbon::now()->addDay(),
            'venue_id' => $venue->id,
            'rule_set_id' => $ruleSet?->id,
            'timezone' => 'Asia/Kathmandu',
        ]);

        $this->seedCategoryWithBracket(
            $t2,
            PlayerGenderEnum::Male,
            AgeCategoryEnum::Seniors,
            WeightCategoryEnum::Male73,
            66.01, 73.00,
            18, 34,
            8,
            $clubs,
        );

        $this->seedCategoryWithBracket(
            $t2,
            PlayerGenderEnum::Female,
            AgeCategoryEnum::Seniors,
            WeightCategoryEnum::Female57,
            52.01, 57.00,
            18, 34,
            6,
            $clubs,
        );

        // ── Tournament 3: Completed — past tournament ──

        Tournament::create([
            'name' => 'Winter Cup 2025',
            'level' => TournamentLevelEnum::Local,
            'status' => TournamentStatusEnum::Completed,
            'start_at' => Carbon::now()->subMonths(2),
            'end_at' => Carbon::now()->subMonths(2)->addDays(2),
            'venue_id' => $venue->id,
            'rule_set_id' => $ruleSet?->id,
            'timezone' => 'Asia/Kathmandu',
        ]);
    }

    private function seedCategoryWithBracket(
        Tournament $tournament,
        PlayerGenderEnum $gender,
        AgeCategoryEnum $ageCategory,
        WeightCategoryEnum $weightCategory,
        float $minWeight,
        float $maxWeight,
        int $minAge,
        int $maxAge,
        int $playerCount,
        $clubs,
    ): void {
        $transitionService = app(CategoryStatusTransitionServiceInterface::class);
        $bracketService = app(BracketServiceInterface::class);

        $category = TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => $gender,
            'age_category' => $ageCategory,
            'min_age' => $minAge,
            'max_age' => $maxAge,
            'weight_category' => $weightCategory,
            'min_weight' => $minWeight,
            'max_weight' => $maxWeight,
            'status' => CategoryStatusEnum::Draft,
            'registration_open_at' => Carbon::now()->subDays(10),
            'registration_close_at' => Carbon::now()->subDays(3),
            'weigh_in_start_at' => Carbon::now()->subDays(2),
            'weigh_in_end_at' => Carbon::now()->subDay(),
            'start_at' => Carbon::now()->subDay(),
            'end_at' => Carbon::now()->addDay(),
        ]);

        $transitionService->transitionTo($category, CategoryStatusEnum::RegistrationOpen);

        $players = $this->createPlayers($gender, $minAge, $maxAge, $minWeight, $maxWeight, $playerCount, $clubs);

        foreach ($players as $player) {
            TournamentRegistration::create([
                'tournament_category_id' => $category->id,
                'club_id' => $clubs->random()->id,
                'player_id' => $player->id,
                'player_age' => Carbon::parse($player->dob)->age,
                'status' => RegistrationStatusEnum::Registered,
            ]);
        }

        $transitionService->transitionTo($category->fresh(), CategoryStatusEnum::RegistrationClosed);

        $bracket = TournamentBracket::create([
            'tournament_category_id' => $category->id,
            'type' => BracketTypeEnum::SingleElimination,
            'seeding_method' => SeedingMethodEnum::Random,
            'status' => BracketStatusEnum::Pending,
        ]);

        $bracketService->generateBracket($category->fresh(), $bracket);
    }

    private function createPlayers(
        PlayerGenderEnum $gender,
        int $minAge,
        int $maxAge,
        float $minWeight,
        float $maxWeight,
        int $count,
        $clubs,
    ) {
        return Player::factory()->count($count)->create([
            'gender' => $gender->value,
            'dob' => fn () => Carbon::now()->subYears(fake()->numberBetween($minAge + 1, $maxAge - 1))->subDays(fake()->numberBetween(0, 364))->toDateString(),
            'weight' => fn () => fake()->randomFloat(2, $minWeight + 0.5, $maxWeight - 0.5),
        ]);
    }
}
