<?php

namespace Tests\Unit\Player;

use App\Modules\Players\Filters\PlayersFilter;
use App\Modules\Players\Models\Player;
use App\Modules\Players\Repositories\PlayerRepository;
use App\System\Player\Foundation\Enums\PlayerGenderEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PlayerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PlayerRepository(new Player);
    }

    // ──────────────────────────────────────────────────────────────
    // getFilter
    // ──────────────────────────────────────────────────────────────

    public function test_get_filter_returns_players_filter_instance(): void
    {
        $builder = Player::query();

        $this->assertInstanceOf(PlayersFilter::class, $this->repository->getFilter($builder));
    }

    // ──────────────────────────────────────────────────────────────
    // create
    // ──────────────────────────────────────────────────────────────

    public function test_create_persists_player_to_database(): void
    {
        $this->repository->create($this->validPayload());

        $this->assertDatabaseHas('players', ['name' => 'John Doe']);
    }

    public function test_create_returns_player_instance(): void
    {
        $result = $this->repository->create($this->validPayload());

        $this->assertInstanceOf(Player::class, $result);
    }

    public function test_create_stores_all_required_fields(): void
    {
        $player = $this->repository->create($this->validPayload());

        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'name' => 'John Doe',
            'gender' => PlayerGenderEnum::MALE->value,
            'dob' => '2000-01-15',
            'weight' => '75.00',
            'nationality' => 'Nepal',
        ]);
    }

    public function test_create_stores_nationality(): void
    {
        $player = $this->repository->create($this->validPayload());

        $this->assertSame('Nepal', $player->nationality);
        $this->assertDatabaseHas('players', ['id' => $player->id, 'nationality' => 'Nepal']);
    }

    public function test_create_stores_null_email_when_not_provided(): void
    {
        $player = $this->repository->create($this->validPayload());

        $this->assertNull($player->email);
    }

    public function test_create_stores_email_when_provided(): void
    {
        $player = $this->repository->create($this->validPayload(['email' => 'john@example.com']));

        $this->assertSame('john@example.com', $player->email);
        $this->assertDatabaseHas('players', ['id' => $player->id, 'email' => 'john@example.com']);
    }

    public function test_create_stores_null_phone_number_when_not_provided(): void
    {
        $player = $this->repository->create($this->validPayload());

        $this->assertNull($player->phone_number);
    }

    public function test_create_stores_phone_number_when_provided(): void
    {
        $player = $this->repository->create($this->validPayload(['phone_number' => '+977 9800000000']));

        $this->assertDatabaseHas('players', ['id' => $player->id, 'phone_number' => '+977 9800000000']);
    }

    public function test_create_stores_null_profile_photo_url_when_not_provided(): void
    {
        $player = $this->repository->create($this->validPayload());

        $this->assertNull($player->profile_photo_url);
    }

    public function test_create_stores_profile_photo_url_when_provided(): void
    {
        $player = $this->repository->create($this->validPayload(['profile_photo_url' => 'images/players/photo.jpg']));

        $this->assertSame('images/players/photo.jpg', $player->profile_photo_url);
    }

    // ──────────────────────────────────────────────────────────────
    // update
    // ──────────────────────────────────────────────────────────────

    public function test_update_changes_player_name(): void
    {
        $player = Player::factory()->create();

        $this->repository->update($player->id, $this->validPayload(['name' => 'Updated Name']));

        $this->assertDatabaseHas('players', ['id' => $player->id, 'name' => 'Updated Name']);
    }

    public function test_update_changes_nationality(): void
    {
        $player = Player::factory()->create(['nationality' => 'Nepal']);

        $this->repository->update($player->id, $this->validPayload(['nationality' => 'Japan']));

        $this->assertDatabaseHas('players', ['id' => $player->id, 'nationality' => 'Japan']);
    }

    public function test_update_changes_email(): void
    {
        $player = Player::factory()->create(['email' => 'old@example.com']);

        $this->repository->update($player->id, $this->validPayload(['email' => 'new@example.com']));

        $this->assertDatabaseHas('players', ['id' => $player->id, 'email' => 'new@example.com']);
    }

    public function test_update_returns_updated_player_instance(): void
    {
        $player = Player::factory()->create();

        $result = $this->repository->update($player->id, $this->validPayload(['name' => 'Renamed']));

        $this->assertInstanceOf(Player::class, $result);
        $this->assertSame('Renamed', $result->name);
    }

    // ──────────────────────────────────────────────────────────────
    // destroy
    // ──────────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_the_player(): void
    {
        $player = Player::factory()->create();

        $this->repository->destroy($player->id);

        $this->assertSoftDeleted('players', ['id' => $player->id]);
    }

    public function test_destroy_does_not_affect_other_players(): void
    {
        $playerA = Player::factory()->create();
        $playerB = Player::factory()->create();

        $this->repository->destroy($playerA->id);

        $this->assertDatabaseHas('players', ['id' => $playerB->id, 'deleted_at' => null]);
    }

    // ──────────────────────────────────────────────────────────────
    // getById
    // ──────────────────────────────────────────────────────────────

    public function test_get_by_id_returns_correct_player(): void
    {
        $player = Player::factory()->create(['name' => 'Specific Player']);

        $result = $this->repository->getById($player->id);

        $this->assertInstanceOf(Player::class, $result);
        $this->assertSame($player->id, $result->id);
        $this->assertSame('Specific Player', $result->name);
    }

    public function test_get_by_id_throws_for_nonexistent_id(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->getById((string) \Illuminate\Support\Str::ulid());
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Doe',
            'gender' => PlayerGenderEnum::MALE->value,
            'dob' => '2000-01-15',
            'weight' => 75.00,
            'nationality' => 'Nepal',
        ], $overrides);
    }
}
