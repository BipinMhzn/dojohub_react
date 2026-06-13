<?php

namespace Tests\Unit\Tournament;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Interfaces\PoolInterface;
use App\System\Club\Models\Club;
use App\System\Player\Models\Player;
use App\System\Tournament\Foundation\Enums\BracketStatusEnum;
use App\System\Tournament\Foundation\Enums\BracketTypeEnum;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\SeedingMethodEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentBracket;
use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentPoolCompetitor;
use App\System\Tournament\Models\TournamentRegistration;
use App\System\Tournament\Models\TournamentWeighIn;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function bracketService(): BracketServiceInterface
    {
        return app(BracketServiceInterface::class);
    }

    private function pools(): PoolInterface
    {
        return app(PoolInterface::class);
    }

    private function makeCategory(Tournament $tournament): TournamentCategory
    {
        $category = TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => 0,
            'age_category' => 8,
            'min_age' => 18,
            'max_age' => 34,
            'weight_category' => 0,
            'min_weight' => 60,
            'max_weight' => 90,
            'status' => CategoryStatusEnum::RegistrationClosed,
        ]);
        $category->setRelation('tournament', $tournament);

        return $category;
    }

    private function makeConfirmedWeighed(TournamentCategory $category): TournamentRegistration
    {
        $registration = TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => Player::factory()->create()->id,
            'player_age' => 25,
            'status' => RegistrationStatusEnum::Confirmed,
        ]);

        TournamentWeighIn::create([
            'registration_id' => $registration->id,
            'weight' => 70,
            'weighed_at' => '2026-06-13 09:00:00',
            'made_weight' => true,
        ]);

        return $registration;
    }

    private function makePoolBracket(TournamentCategory $category): TournamentBracket
    {
        return TournamentBracket::create([
            'tournament_category_id' => $category->id,
            'type' => BracketTypeEnum::PoolFinal,
            'seeding_method' => SeedingMethodEnum::Random,
            'status' => BracketStatusEnum::Pending,
        ]);
    }

    private function setupCategoryWithCompetitors(int $count): array
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);

        for ($i = 0; $i < $count; $i++) {
            $this->makeConfirmedWeighed($category);
        }

        return [$category, $this->makePoolBracket($category)];
    }

    public function test_generate_pools_distributes_competitors_and_activates_bracket(): void
    {
        [$category, $bracket] = $this->setupCategoryWithCompetitors(6);

        $pools = $this->bracketService()->generatePools($category, $bracket, 2);

        $this->assertCount(2, $pools);
        $this->assertSame(CategoryStatusEnum::BracketGenerated, $category->fresh()->status);
        $this->assertSame(BracketStatusEnum::Active, $bracket->fresh()->status);
        $this->assertSame(6, $bracket->fresh()->competitor_count);

        // Snake distribution keeps the two pools balanced at 3 each.
        $this->assertSame(6, TournamentPoolCompetitor::whereIn('pool_id', $pools->pluck('id'))->count());
        foreach ($pools as $pool) {
            $this->assertSame(3, TournamentPoolCompetitor::where('pool_id', $pool->id)->count());
        }
    }

    public function test_generate_pools_rejects_non_pool_bracket(): void
    {
        [$category] = $this->setupCategoryWithCompetitors(4);
        $eliminationBracket = TournamentBracket::create([
            'tournament_category_id' => $category->id,
            'type' => BracketTypeEnum::SingleElimination,
            'seeding_method' => SeedingMethodEnum::Random,
            'status' => BracketStatusEnum::Pending,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pool + Final');

        $this->bracketService()->generatePools($category, $eliminationBracket, 2);
    }

    public function test_generate_pools_rejects_more_pools_than_competitors(): void
    {
        [$category, $bracket] = $this->setupCategoryWithCompetitors(3);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('number of pools must be between');

        $this->bracketService()->generatePools($category, $bracket, 5);
    }

    public function test_generate_pools_requires_completed_weigh_in(): void
    {
        $tournament = Tournament::factory()->create();
        $category = $this->makeCategory($tournament);
        $this->makeConfirmedWeighed($category);
        // Second competitor has no weigh-in.
        TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => Player::factory()->create()->id,
            'player_age' => 25,
            'status' => RegistrationStatusEnum::Confirmed,
        ]);
        $bracket = $this->makePoolBracket($category);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('still need to be weighed in');

        $this->bracketService()->generatePools($category, $bracket, 2);
    }

    public function test_redistributing_clears_previous_pools(): void
    {
        [$category, $bracket] = $this->setupCategoryWithCompetitors(6);

        $firstPools = $this->bracketService()->generatePools($category, $bracket, 2);
        $secondPools = $this->bracketService()->generatePools($category->fresh(), $bracket->fresh(), 3);

        $this->assertCount(3, $secondPools);
        // Old pools and their competitors are soft-deleted.
        foreach ($firstPools as $pool) {
            $this->assertSoftDeleted('tournament_pools', ['id' => $pool->id]);
        }
        $this->assertSame(6, TournamentPoolCompetitor::whereIn('pool_id', $secondPools->pluck('id'))->count());
    }

    public function test_assign_competitor_moves_them_to_a_single_pool(): void
    {
        [$category, $bracket] = $this->setupCategoryWithCompetitors(6);
        $pools = $this->bracketService()->generatePools($category, $bracket, 2);

        $poolA = $pools[0];
        $poolB = $pools[1];
        $memberOfA = TournamentPoolCompetitor::where('pool_id', $poolA->id)->first();

        $this->pools()->assignCompetitor($category->fresh(), $poolB->id, $memberOfA->registration_id);

        // No longer in A, now in B — exactly one active membership.
        $this->assertSame(0, TournamentPoolCompetitor::where('pool_id', $poolA->id)->where('registration_id', $memberOfA->registration_id)->count());
        $this->assertSame(1, TournamentPoolCompetitor::where('pool_id', $poolB->id)->where('registration_id', $memberOfA->registration_id)->count());
    }

    public function test_remove_competitor_soft_deletes_membership(): void
    {
        [$category, $bracket] = $this->setupCategoryWithCompetitors(4);
        $pools = $this->bracketService()->generatePools($category, $bracket, 2);
        $member = TournamentPoolCompetitor::where('pool_id', $pools[0]->id)->first();

        $this->pools()->removeCompetitor($category->fresh(), $member->id);

        $this->assertSoftDeleted('tournament_pool_competitors', ['id' => $member->id]);
    }

    public function test_pool_mutations_blocked_when_tournament_cancelled(): void
    {
        [$category, $bracket] = $this->setupCategoryWithCompetitors(4);
        $pools = $this->bracketService()->generatePools($category, $bracket, 2);
        $member = TournamentPoolCompetitor::where('pool_id', $pools[0]->id)->first();

        $category->tournament->update(['status' => TournamentStatusEnum::Cancelled]);

        $this->expectException(TournamentCancelledException::class);

        $this->pools()->removeCompetitor($category->fresh()->load('tournament'), $member->id);
    }
}
