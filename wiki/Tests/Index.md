# Test Suite — Wiki

---

## Overview

DojoHub uses PHPUnit with two test suites: **Unit** (repository-level) and **Feature** (HTTP/Inertia integration). All tests run against an in-memory SQLite database with `RefreshDatabase`.

---

## Running Tests

```bash
# Run all tests
php artisan test

# Run with PHPUnit directly
./vendor/bin/phpunit

# Run a single suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature

# Run a single file
./vendor/bin/phpunit tests/Feature/VenueTest.php

# Stop on first failure
./vendor/bin/phpunit --stop-on-failure

# With coverage (requires Xdebug or PCOV)
./vendor/bin/phpunit --coverage-text
```

---

## Configuration (`phpunit.xml`)

| Setting | Value | Notes |
|---------|-------|-------|
| `APP_ENV` | `testing` | — |
| `DB_CONNECTION` | `sqlite` | In-memory SQLite |
| `DB_DATABASE` | `:memory:` | No disk writes |
| `CACHE_STORE` | `array` | No persistence |
| `QUEUE_CONNECTION` | `sync` | Jobs run inline |
| `SESSION_DRIVER` | `array` | No disk |
| `MAIL_MAILER` | `array` | Emails captured, not sent |
| `BCRYPT_ROUNDS` | `4` | Faster password hashing in tests |
| `TELESCOPE_ENABLED` | `false` | — |
| `PULSE_ENABLED` | `false` | — |
| `AUDITING_ENABLED` | — | Defaults to `true`; set `false` if audit writes slow tests |

---

## Test Structure

```
tests/
├── TestCase.php                              # Minimal base — extends Laravel's TestCase
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php           # Login, logout
│   │   ├── EmailVerificationTest.php        # Email verification flow
│   │   ├── PasswordConfirmationTest.php     # Confirm-password flow
│   │   ├── PasswordResetTest.php            # Password reset (disabled routes)
│   │   └── RegistrationTest.php             # Registration (disabled routes)
│   ├── Settings/
│   │   ├── PasswordUpdateTest.php           # PUT /settings/password
│   │   └── ProfileUpdateTest.php            # PATCH /settings/profile
│   ├── ClubCoachTest.php                    # Club–coach membership CRUD
│   ├── ClubPlayerTest.php                   # Club–player membership CRUD
│   ├── CoachTest.php                        # Coach CRUD HTTP
│   ├── DashboardTest.php                    # Dashboard access
│   ├── RefereeTest.php                      # Referee CRUD HTTP
│   ├── TournamentTest.php                   # Tournament + brackets + matches HTTP
│   └── VenueTest.php                        # Venue + mat CRUD HTTP
└── Unit/
    ├── ExampleTest.php                       # Default scaffold (passes trivially)
    ├── Club/
    │   └── ClubRepositoryTest.php           # ClubRepository unit tests
    ├── Clubs/
    │   └── ClubCoachRepositoryTest.php      # ClubCoachRepository unit tests
    ├── Coach/
    │   └── CoachRepositoryTest.php          # CoachRepository unit tests
    ├── Player/
    │   └── PlayerRepositoryTest.php         # PlayerRepository unit tests
    ├── Referee/
    │   └── RefereeRepositoryTest.php        # RefereeRepository unit tests
    └── Venue/
        └── VenueRepositoryTest.php          # VenueRepository + VenueMatRepository unit tests
```

---

## TestCase Base

`tests/TestCase.php` extends `Illuminate\Foundation\Testing\TestCase` with no additional setup. All database isolation is handled per-test via the `RefreshDatabase` trait.

```php
abstract class TestCase extends BaseTestCase
{
    // No custom setUp — clean and minimal
}
```

---

## Unit Tests — Repository Pattern

Unit tests instantiate repositories directly (no HTTP layer). They use `RefreshDatabase` with SQLite in-memory.

### Conventions

```php
class VenueRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal']); // required FK seed
        $this->repository = new VenueRepository(new Venue, new Address);
    }
}
```

**Key patterns:**
- Repository is instantiated with `new`, not resolved from container — pure unit test
- Country is pre-seeded where needed (FK constraint on `nationality_code` / `country_code`)
- Factory helpers used for pre-existing models: `Venue::factory()->create()`
- `validPayload()` private helper provides base valid data with `array_merge` overrides
- Tests organised into sections with comment separators (`// ─── createVenue ───`)

### Coverage by Repository Test

| Test File | Repository Methods Covered |
|-----------|--------------------------|
| `VenueRepositoryTest` | `createVenue`, `updateVenue`, `deleteVenue`, `getById`, VenueMatRepository CRUD |
| `ClubRepositoryTest` | `createClub`, `updateClub`, `deleteClub`, `getById` |
| `ClubCoachRepositoryTest` | Coach–club assignment and membership management |
| `CoachRepositoryTest` | `create`, `update`, `destroy`, `getById`, `getList` |
| `PlayerRepositoryTest` | `create`, `update`, `destroy`, `getById`, `getList` |
| `RefereeRepositoryTest` | `create`, `update`, `destroy`, `getById`, `getList` |

---

## Feature Tests — HTTP Pattern

Feature tests hit the full HTTP stack via `actingAs()` + route methods.

### Conventions

```php
class VenueTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();  // fresh user per call
    }

    private function validVenuePayload(array $overrides = []): array
    {
        return array_merge([/* defaults */], $overrides);
    }
}
```

**Test groups per resource:**
1. **Guest redirect** — unauthenticated requests redirect to `/login`
2. **Index** — authenticated list page loads
3. **Create** — create page loads
4. **Store** — valid data creates record, invalid data returns errors
5. **Show** — show page loads, 404 on missing
6. **Update** — valid updates persist, validation errors returned
7. **Destroy** — soft-delete succeeds, FK constraint blocked delete returns error flash

### Feature Test Coverage Map

| Test File | Routes Covered |
|-----------|--------------|
| `VenueTest` | `/manage/venues` (full CRUD + mats CRUD) |
| `CoachTest` | `/manage/coaches` (full CRUD) |
| `RefereeTest` | `/manage/referees` (full CRUD) |
| `ClubPlayerTest` | Club–player assignment, membership end, belt update |
| `ClubCoachTest` | Club–coach assignment and membership |
| `TournamentTest` | Tournament CRUD + bracket generation + match management |
| `DashboardTest` | `GET /dashboard` auth check |
| `Auth/AuthenticationTest` | Login, logout, invalid credentials |
| `Auth/EmailVerificationTest` | Verification prompt, verification link, resend |
| `Auth/PasswordConfirmationTest` | Confirm-password page and submission |
| `Settings/ProfileUpdateTest` | Profile edit page and update |
| `Settings/PasswordUpdateTest` | Password change page and update |

---

## FK Seeding Pattern

SQLite in-memory does not auto-populate reference tables. Tests that create models with FK columns must seed the lookup tables first:

```php
// Required before creating any model with nationality_code or country_code
Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal']);

// Required before creating players with current_belt_rank_id
BeltRank::firstOrCreate([...]);
```

This is typically done in `setUp()` for tests that need it.

---

## Useful Assertions

```php
// Inertia page rendered
$response->assertInertia(fn ($page) => $page->component('venues/index'));

// Flash messages
$response->assertSessionHas('success');
$response->assertSessionHas('error');

// Soft delete
$this->assertSoftDeleted('venues', ['id' => $venue->id]);

// DB state
$this->assertDatabaseHas('venues', ['name' => 'Test Hall']);
$this->assertDatabaseMissing('venues', ['name' => 'Deleted Hall', 'deleted_at' => null]);
$this->assertDatabaseCount('venues', 3);

// Redirects
$response->assertRedirect('/manage/venues');
$response->assertRedirect("/manage/venues/{$venue->id}");

// Validation errors
$response->assertSessionHasErrors(['name', 'city']);
$response->assertSessionHasNoErrors();
```

---

## Related

- [.claude/guidelines/testing-patterns.md](../../.claude/guidelines/testing-patterns.md) — coding guidelines for writing new tests
- [Database/Factories.md](../Database/Factories.md) — factory definitions
- [Database/Seeders.md](../Database/Seeders.md) — seeder classes
