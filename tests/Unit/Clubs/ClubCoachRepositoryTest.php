<?php

namespace Tests\Unit\Clubs;

use App\Modules\Clubs\Filters\ClubCoachesFilter;
use App\Modules\Clubs\Models\ClubCoach;
use App\Modules\Clubs\Repositories\ClubCoachRepository;
use App\Modules\Coaches\Models\Coach;
use App\System\Club\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubCoachRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ClubCoachRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ClubCoachRepository(new ClubCoach, new Coach);
    }

    public function test_get_filter_returns_filter_instance(): void
    {
        $this->assertInstanceOf(ClubCoachesFilter::class, $this->repository->getFilter(ClubCoach::query()));
    }

    public function test_get_club_coaches_paginates_memberships_for_a_club(): void
    {
        $club = Club::factory()->create();
        ClubCoach::factory()->count(3)->create(['club_id' => $club->id]);
        ClubCoach::factory()->count(2)->create(); // other clubs

        $result = $this->repository->getClubCoaches($club->id, []);

        $this->assertSame(3, $result->total());
    }

    public function test_get_available_coaches_excludes_active_members_of_the_club(): void
    {
        $club = Club::factory()->create();
        $active = Coach::factory()->create();
        $endedAtThisClub = Coach::factory()->create();
        $unrelated = Coach::factory()->create();

        ClubCoach::factory()->create(['club_id' => $club->id, 'coach_id' => $active->id]);
        ClubCoach::factory()->ended()->create(['club_id' => $club->id, 'coach_id' => $endedAtThisClub->id]);

        $available = $this->repository->getAvailableCoaches($club->id)->pluck('id')->all();

        $this->assertNotContains($active->id, $available);
        $this->assertContains($endedAtThisClub->id, $available);
        $this->assertContains($unrelated->id, $available);
    }

    public function test_assign_coach_creates_a_membership(): void
    {
        $club = Club::factory()->create();
        $coach = Coach::factory()->create();

        $membership = $this->repository->assignCoach($club->id, [
            'coach_id' => $coach->id,
            'started_at' => now()->format('Y-m-d'),
        ]);

        $this->assertInstanceOf(ClubCoach::class, $membership);
        $this->assertDatabaseHas('clubs_coaches', [
            'club_id' => $club->id,
            'coach_id' => $coach->id,
            'ended_at' => null,
        ]);
    }

    public function test_assign_coach_throws_for_duplicate_active_membership(): void
    {
        $club = Club::factory()->create();
        $coach = Coach::factory()->create();
        ClubCoach::factory()->create(['club_id' => $club->id, 'coach_id' => $coach->id]);

        $this->expectException(\RuntimeException::class);
        $this->repository->assignCoach($club->id, [
            'coach_id' => $coach->id,
            'started_at' => now()->format('Y-m-d'),
        ]);
    }

    public function test_end_membership_sets_ended_at(): void
    {
        $club = Club::factory()->create();
        $membership = ClubCoach::factory()->create([
            'club_id' => $club->id,
            'started_at' => now()->subYear(),
        ]);

        $endedAt = now()->format('Y-m-d');
        $this->repository->endMembership($club->id, $membership->id, ['ended_at' => $endedAt]);

        $this->assertNotNull($membership->fresh()->ended_at);
    }
}
