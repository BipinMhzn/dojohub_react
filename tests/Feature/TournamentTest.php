<?php

namespace Tests\Feature;

use App\Modules\Countries\Models\Country;
use App\System\Tournament\Foundation\Enums\TournamentLevelEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\TournamentRuleSet\Models\TournamentRuleSet;
use App\System\User\Models\User;
use App\System\Venue\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use RefreshDatabase;

    private ?TournamentRuleSet $ruleSet = null;

    protected function setUp(): void
    {
        parent::setUp();
        Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal']);
        $this->ruleSet = TournamentRuleSet::create([
            'name' => 'IJF 2024',
            'max_waza_ari' => 2,
            'max_shido' => 3,
            'match_duration_seconds' => 240,
        ]);
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        $venue = Venue::factory()->create();

        return array_merge([
            'name' => 'Spring Open 2026',
            'level' => TournamentLevelEnum::National->value,
            'start_at' => now()->addWeek()->toDateString(),
            'venue_id' => $venue->id,
        ], $overrides);
    }

    // ──────────────────────────────────────────────────────────────
    // Guest / auth redirect
    // ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_from_tournaments_index(): void
    {
        $this->get('/manage/tournaments')->assertRedirect('/login');
    }

    public function test_guests_are_redirected_from_tournaments_create_page(): void
    {
        $this->get('/manage/tournaments/create')->assertRedirect('/login');
    }

    public function test_guests_cannot_store_a_tournament(): void
    {
        $this->post('/manage/tournaments', $this->validPayload())->assertRedirect('/login');
    }

    public function test_guests_cannot_update_a_tournament(): void
    {
        $tournament = Tournament::factory()->create();
        $this->post("/manage/tournaments/{$tournament->id}", $this->validPayload())->assertRedirect('/login');
    }

    public function test_guests_cannot_delete_a_tournament(): void
    {
        $this->delete('/manage/tournaments/'.Str::ulid())->assertRedirect('/login');
    }

    // ──────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_tournaments_list(): void
    {
        $this->actingAs($this->user())->get('/manage/tournaments')->assertOk();
    }

    public function test_tournaments_list_shows_all_existing_tournaments(): void
    {
        Tournament::factory()->count(3)->create();

        $this->actingAs($this->user())->get('/manage/tournaments')->assertOk();

        $this->assertDatabaseCount('tournaments', 3);
    }

    // ──────────────────────────────────────────────────────────────
    // Create page
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_create_tournament_page(): void
    {
        $this->actingAs($this->user())->get('/manage/tournaments/create')->assertOk();
    }

    // ──────────────────────────────────────────────────────────────
    // Store
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_a_tournament(): void
    {
        $response = $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('tournaments', ['name' => 'Spring Open 2026']);
    }

    public function test_store_sets_status_to_scheduled(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload());

        $this->assertDatabaseHas('tournaments', [
            'name' => 'Spring Open 2026',
            'status' => TournamentStatusEnum::Scheduled->value,
        ]);
    }

    public function test_store_saves_rule_set_id(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload([
                'rule_set_id' => $this->ruleSet->id,
            ]));

        $this->assertDatabaseHas('tournaments', [
            'name' => 'Spring Open 2026',
            'rule_set_id' => $this->ruleSet->id,
        ]);
    }

    public function test_store_saves_timezone(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload([
                'timezone' => 'Asia/Kathmandu',
            ]));

        $this->assertDatabaseHas('tournaments', [
            'name' => 'Spring Open 2026',
            'timezone' => 'Asia/Kathmandu',
        ]);
    }

    public function test_store_allows_nullable_rule_set_id(): void
    {
        $response = $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload(['rule_set_id' => null]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tournaments', ['name' => 'Spring Open 2026', 'rule_set_id' => null]);
    }

    public function test_store_allows_nullable_timezone(): void
    {
        $response = $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload(['timezone' => null]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tournaments', ['name' => 'Spring Open 2026', 'timezone' => null]);
    }

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_start_at(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload(['start_at' => '']))
            ->assertSessionHasErrors('start_at');
    }

    public function test_store_requires_venue_id(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload(['venue_id' => '']))
            ->assertSessionHasErrors('venue_id');
    }

    public function test_store_requires_valid_venue_id(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload(['venue_id' => Str::ulid()]))
            ->assertSessionHasErrors('venue_id');
    }

    public function test_store_requires_valid_level(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload(['level' => 99]))
            ->assertSessionHasErrors('level');
    }

    public function test_store_rejects_invalid_rule_set_id(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload(['rule_set_id' => Str::ulid()]))
            ->assertSessionHasErrors('rule_set_id');
    }

    public function test_store_rejects_invalid_timezone(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments', $this->validPayload(['timezone' => 'Invalid/Zone']))
            ->assertSessionHasErrors('timezone');
    }

    // ──────────────────────────────────────────────────────────────
    // Show page
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_tournament_show_page(): void
    {
        $tournament = Tournament::factory()->create();

        $this->actingAs($this->user())
            ->get("/manage/tournaments/{$tournament->id}")
            ->assertOk();
    }

    public function test_show_page_returns_404_for_nonexistent_tournament(): void
    {
        $this->actingAs($this->user())
            ->get('/manage/tournaments/'.Str::ulid())
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Edit (redirects to show)
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_visiting_edit_is_redirected_to_show(): void
    {
        $tournament = Tournament::factory()->create();

        $this->actingAs($this->user())
            ->get("/manage/tournaments/{$tournament->id}/edit")
            ->assertRedirect("/manage/tournaments/{$tournament->id}");
    }

    // ──────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_a_tournament(): void
    {
        $tournament = Tournament::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user())
            ->post("/manage/tournaments/{$tournament->id}", $this->validPayload(['name' => 'Updated Name']));

        $response->assertRedirect("/manage/tournaments/{$tournament->id}");
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tournaments', ['id' => $tournament->id, 'name' => 'Updated Name']);
    }

    public function test_update_saves_rule_set_id(): void
    {
        $tournament = Tournament::factory()->create();

        $this->actingAs($this->user())
            ->post("/manage/tournaments/{$tournament->id}", $this->validPayload([
                'rule_set_id' => $this->ruleSet->id,
            ]));

        $this->assertDatabaseHas('tournaments', [
            'id' => $tournament->id,
            'rule_set_id' => $this->ruleSet->id,
        ]);
    }

    public function test_update_saves_timezone(): void
    {
        $tournament = Tournament::factory()->create();

        $this->actingAs($this->user())
            ->post("/manage/tournaments/{$tournament->id}", $this->validPayload([
                'timezone' => 'Europe/London',
            ]));

        $this->assertDatabaseHas('tournaments', [
            'id' => $tournament->id,
            'timezone' => 'Europe/London',
        ]);
    }

    public function test_update_requires_name(): void
    {
        $tournament = Tournament::factory()->create();

        $this->actingAs($this->user())
            ->post("/manage/tournaments/{$tournament->id}", $this->validPayload(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_update_returns_404_for_nonexistent_tournament(): void
    {
        $this->actingAs($this->user())
            ->post('/manage/tournaments/'.Str::ulid(), $this->validPayload())
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Destroy
    // ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_delete_a_tournament(): void
    {
        $tournament = Tournament::factory()->create();

        $response = $this->actingAs($this->user())->delete("/manage/tournaments/{$tournament->id}");

        $response->assertRedirect('/manage/tournaments');
        $this->assertSoftDeleted('tournaments', ['id' => $tournament->id]);
    }

    public function test_deleting_a_tournament_does_not_affect_other_tournaments(): void
    {
        $tournamentA = Tournament::factory()->create();
        $tournamentB = Tournament::factory()->create();

        $this->actingAs($this->user())->delete("/manage/tournaments/{$tournamentA->id}");

        $this->assertSoftDeleted('tournaments', ['id' => $tournamentA->id]);
        $this->assertDatabaseHas('tournaments', ['id' => $tournamentB->id, 'deleted_at' => null]);
    }

    public function test_delete_returns_404_for_nonexistent_tournament(): void
    {
        $this->actingAs($this->user())
            ->delete('/manage/tournaments/'.Str::ulid())
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Eager loading
    // ──────────────────────────────────────────────────────────────

    public function test_show_page_eager_loads_rule_set(): void
    {
        $tournament = Tournament::factory()->create(['rule_set_id' => $this->ruleSet->id]);

        $response = $this->actingAs($this->user())
            ->get("/manage/tournaments/{$tournament->id}");

        $response->assertOk();
        $page = $response->original->getData()['page'];
        $tournamentProp = $page['props']['tournament'];
        $this->assertNotNull($tournamentProp['rule_set']);
        $this->assertSame($this->ruleSet->id, $tournamentProp['rule_set']['id']);
    }
}
