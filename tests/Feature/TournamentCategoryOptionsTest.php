<?php

namespace Tests\Feature;

use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentCategory;
use App\System\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentCategoryOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(Tournament $tournament, int $weightCategory): TournamentCategory
    {
        return TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => 0,
            'age_category' => 8,
            'min_age' => 18,
            'max_age' => 34,
            'weight_category' => $weightCategory,
            'min_weight' => 60,
            'max_weight' => 90,
            'status' => CategoryStatusEnum::Draft,
        ]);
    }

    private function url(Tournament $tournament): string
    {
        return route('tournaments.categories.options', [$tournament->id], false);
    }

    public function test_guests_are_redirected(): void
    {
        $tournament = Tournament::factory()->create();

        $this->get($this->url($tournament))->assertRedirect('/login');
    }

    public function test_returns_categories_for_the_tournament(): void
    {
        $tournament = Tournament::factory()->create();
        $first = $this->makeCategory($tournament, 0);
        $second = $this->makeCategory($tournament, 1);

        $response = $this->actingAs(User::factory()->create())
            ->getJson($this->url($tournament));

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['id' => $first->id]);
        $response->assertJsonFragment(['id' => $second->id]);
    }

    public function test_excludes_categories_from_other_tournaments(): void
    {
        $tournament = Tournament::factory()->create();
        $own = $this->makeCategory($tournament, 0);

        $other = Tournament::factory()->create();
        $foreign = $this->makeCategory($other, 0);

        $response = $this->actingAs(User::factory()->create())
            ->getJson($this->url($tournament));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $own->id]);
        $response->assertJsonMissing(['id' => $foreign->id]);
    }
}
