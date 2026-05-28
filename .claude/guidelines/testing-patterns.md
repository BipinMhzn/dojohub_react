# Testing Patterns Guideline

How tests are organized and written in dojohub_react.

---

## Test infrastructure

| Aspect | Setting |
|--------|---------|
| **Framework** | PHPUnit 11 |
| **Base class** | `Tests\TestCase` (extends `Illuminate\Foundation\Testing\TestCase`) |
| **DB isolation** | `RefreshDatabase` trait — full migration + rollback per test |
| **Test DB** | SQLite in-memory (set in `phpunit.xml`) |
| **Run command** | `composer test` or `php artisan test` |
| **Filter** | `php artisan test --filter=Name` |

Locations:
- `tests/Feature/` — HTTP-level tests (most tests live here)
- `tests/Unit/` — Pure logic tests (rare; no DB)

---

## Standard test class skeleton

```php
<?php

namespace Tests\Feature;

use App\System\User\Models\User;
use App\System\Venue\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Reusable seed data goes here
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    /** @return array<string, mixed> */
    private function validVenuePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kathmandu Sports Hall',
            'address_line_1' => '123 Durbar Marg',
            'city' => 'Kathmandu',
            'country_code' => 'NP',
        ], $overrides);
    }

    // Tests go here…
}
```

Reference: `tests/Feature/VenueTest.php`, `tests/Feature/ClubPlayerTest.php`, `tests/Feature/TournamentTest.php`.

---

## Test naming

- Method name: `test_<actor>_<action>_<expected_outcome>`
- Use `snake_case` with full sentences

```php
public function test_guests_are_redirected_from_venues_index(): void
public function test_authenticated_user_can_view_venues_list(): void
public function test_storing_a_venue_with_missing_name_fails_validation(): void
```

---

## Section banners

Long test files group related tests with comment banners:

```php
// ──────────────────────────────────────────────────────────────
// Guest / auth redirect
// ──────────────────────────────────────────────────────────────

public function test_guests_are_redirected_from_venues_index(): void { ... }

// ──────────────────────────────────────────────────────────────
// Index
// ──────────────────────────────────────────────────────────────

public function test_authenticated_user_can_view_venues_list(): void { ... }
```

Standard sections:
- Guest / auth redirect
- Index
- Create / Show
- Store (validation, success)
- Update (validation, success)
- Destroy (success, FK protection)

---

## What every module test must cover

| Scenario | Status code / Assertion |
|----------|------------------------|
| Guest hits any route | `assertRedirect('/login')` |
| Authed user views list | `assertOk()` + `assertInertia` for component name |
| Store with valid data | `assertRedirect(route(...))` + DB has row + flash `success` |
| Store with missing field | `assertSessionHasErrors(['field'])` |
| Update with valid data | DB row updated + flash `success` |
| Destroy success | `assertSoftDeleted($model)` |
| Destroy with FK constraint | `back()` redirect + flash `error` |

---

## Factories

Every System model has a factory:

```php
// Use the factory
$venue = Venue::factory()->create();

// With overrides
$venue = Venue::factory()->create(['name' => 'Custom']);

// Without persistence
$venue = Venue::factory()->make();

// Multiple
Venue::factory()->count(5)->create();
```

Factories live in `database/factories/`. **Use factory states** for variants (e.g. `Player::factory()->junior()->create()`) instead of inline overrides where possible.

---

## Test data — payload helpers

For request data, create a private helper that returns a complete valid payload with `$overrides`:

```php
/** @return array<string, mixed> */
private function validVenuePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Kathmandu Sports Hall',
        'address_line_1' => '123 Durbar Marg',
        'city' => 'Kathmandu',
        'country_code' => 'NP',
    ], $overrides);
}

// In a test
$this->post('/manage/venues', $this->validVenuePayload(['name' => '']));
```

---

## Inertia assertions

For routes returning Inertia responses:

```php
$this->actingAs($this->user())
    ->get('/manage/venues')
    ->assertOk()
    ->assertInertia(fn (Assert $page) => $page
        ->component('venues/index')
        ->has('venues.data', 5)
        ->where('filters.search', null)
    );
```

Import: `use Inertia\Testing\AssertableInertia as Assert;`

---

## DB assertions

```php
// Soft-deleted
$this->assertSoftDeleted($venue);
$this->assertSoftDeleted('venues', ['id' => $venue->id]);

// Exists with values
$this->assertDatabaseHas('venues', ['name' => 'New Hall']);

// Doesn't exist
$this->assertDatabaseMissing('venues', ['id' => $venue->id]);

// Count
$this->assertDatabaseCount('venues', 3);
```

---

## Avoid

| Anti-pattern | Why |
|--------------|-----|
| Reading from DB to assert (`Venue::find($id)->name`) | Use `assertDatabaseHas` instead — clearer intent |
| Tinker / verification scripts to "test" a feature | Write a real test — they cost the same time and stay around |
| Mocking the repository in a Feature test | Feature tests exercise the real stack; mock only in Unit tests |
| Calling `Carbon::now()` directly without freezing time | Use `$this->travel(...)` or `Carbon::setTestNow()` |
| Asserting against `dd()` output | Use `assertInertia` / `assertJson` / `assertSee` |

---

## Coverage expectations

- Every new module endpoint **must** have at least one happy-path test + at least one validation test
- Every new repository method writing to multiple tables **must** be exercised via a Feature test that asserts both tables changed
- Every new soft-delete + FK RESTRICT path needs a test for both success and FK-blocked cases

---

## Running tests

```bash
composer test                              # Full suite (clears config first)
php artisan test                           # Same, without config clear
php artisan test --filter=VenueTest        # One class
php artisan test --filter=test_guests_are_redirected_from_venues_index   # One method
php artisan test --parallel                # Parallel runner (Paratest)
php artisan test --coverage                # Coverage report (slower)
```

---

## Related docs

- [wiki/Conventions.md](../../wiki/Conventions.md#testing)
- [tests/Feature/VenueTest.php](../../tests/Feature/VenueTest.php) — Reference implementation
- [tests/Feature/TournamentTest.php](../../tests/Feature/TournamentTest.php) — Nested resource testing
