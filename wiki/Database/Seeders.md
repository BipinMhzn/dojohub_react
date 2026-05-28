# Database Seeders

All seeder files live in `database/seeders/`. Seeders fall into two categories:

- **Essential seeders** — called by `DatabaseSeeder` and required for the application to function correctly (static reference data, roles, default admin).
- **Dev-only seeders** — not called by `DatabaseSeeder`; must be run manually to populate test/development data.

Run all essential seeders:

```bash
php artisan db:seed
```

Run a single seeder manually:

```bash
php artisan db:seed --class=ClubSeeder
```

---

## DatabaseSeeder

**File:** `database/seeders/DatabaseSeeder.php`

The entry point. Calls essential seeders in dependency order:

```php
$this->call([
    CountrySeeder::class,           // 1. reference data — no deps
    BeltRankSeeder::class,          // 2. reference data — no deps
    TournamentRuleSetSeeder::class, // 3. reference data — no deps
    RoleSeeder::class,              // 4. Spatie roles — no deps
    DefaultAdminSeeder::class,      // 5. admin user — depends on RoleSeeder
]);
```

Dev-only seeders (`ClubSeeder`, `PlayerSeeder`, `VenueSeeder`, `TournamentSeeder`) are **not** called here.

---

## Essential Seeders

### CountrySeeder

**File:** `database/seeders/CountrySeeder.php`

**What it creates:** All 195 UN-recognized countries as ISO 3166-1 alpha-2 records in the `countries` table.

**Method:** `Country::upsert($rows, ['code'], ['name'])` — inserts all rows in a single statement; updates `name` on conflict so corrections propagate on re-seed.

**Record count:** 195 rows.

**Dependencies:** none — `countries` has no FK dependencies.

**Sample records:**

| code | name |
|---|---|
| `NP` | Nepal |
| `JP` | Japan |
| `US` | United States |
| `GB` | United Kingdom |

---

### BeltRankSeeder

**File:** `database/seeders/BeltRankSeeder.php`

**What it creates:** The 16 standard judo/martial-arts belt ranks in the `belt_ranks` table, from 6th Kyu (beginner) to 10th Dan (grandmaster).

**Method:** `BeltRank::firstOrCreate(['code' => ...], $data)` per row — idempotent; skips rows that already exist.

**Record count:** 16 rows.

**Dependencies:** none.

**Full list:**

| sort_order | code | name | kind | color |
|---|---|---|---|---|
| 1 | `6K` | Rokkyu | 0 (Kyu) | White |
| 2 | `5K` | Gokyu | 0 | Yellow |
| 3 | `4K` | Yonkyu | 0 | Orange |
| 4 | `3K` | Sankyu | 0 | Green |
| 5 | `2K` | Nikyu | 0 | Blue |
| 6 | `1K` | Ikkyu | 0 | Brown |
| 7 | `1D` | Shodan | 1 (Dan) | Black |
| 8 | `2D` | Nidan | 1 | Black |
| 9 | `3D` | Sandan | 1 | Black |
| 10 | `4D` | Yondan | 1 | Black |
| 11 | `5D` | Godan | 1 | Black |
| 12 | `6D` | Rokudan | 1 | Red/White |
| 13 | `7D` | Shichidan | 1 | Red/White |
| 14 | `8D` | Hachidan | 1 | Red/White |
| 15 | `9D` | Kudan | 1 | Red |
| 16 | `10D` | Judan | 1 | Red |

---

### TournamentRuleSetSeeder

**File:** `database/seeders/TournamentRuleSetSeeder.php`

**What it creates:** One `tournament_rule_sets` record representing the current IJF standard ruleset.

**Method:** `TournamentRuleSet::firstOrCreate(['name' => 'IJF 2024'], $data)` — idempotent.

**Record count:** 1 row.

**Dependencies:** none.

**Record created:**

| name | max_waza_ari | max_shido | match_duration_seconds | golden_score_seconds |
|---|---|---|---|---|
| IJF 2024 | 2 | 3 | 240 (4 min) | null (unlimited) |

---

### RoleSeeder

**File:** `database/seeders/RoleSeeder.php`

**What it creates:** Five application roles in Spatie's `roles` table, all for the `web` guard.

**Method:** `Role::firstOrCreate(['name' => ..., 'guard_name' => 'web'])` per role — idempotent.

**Record count:** 5 rows.

**Dependencies:** Spatie permission tables must exist (migration `2026_05_22_151258`).

**Roles created:**

| name | description |
|---|---|
| `super_admin` | Full platform access |
| `tournament_manager` | Creates and manages tournaments |
| `club_admin` | Manages their own club and player registrations |
| `referee` | Access to match management |
| `coach` | Access to coach-specific views |

---

### DefaultAdminSeeder

**File:** `database/seeders/DefaultAdminSeeder.php`

**What it creates:** One superuser account and assigns it the `super_admin` role.

**Method:** `User::firstOrCreate(['email' => 'superuser@dojohub.com'], $data)` — idempotent.

**Record count:** 1 user row; 1 `model_has_roles` pivot row.

**Dependencies:** `RoleSeeder` must have run first (the `super_admin` role must exist).

**Credentials created:**

| email | name | password |
|---|---|---|
| `superuser@dojohub.com` | Superuser | `password` (bcrypt) |

> Change this password immediately in any non-development environment.

---

## Dev-only Seeders

These seeders are **not** called by `DatabaseSeeder`. Run them manually when you need realistic data in a local or staging database.

### ClubSeeder

**File:** `database/seeders/ClubSeeder.php`

**What it creates:** 50 random clubs via `Club::factory()->count(50)->create()`.

**Dependencies:** `ClubFactory` → `AddressFactory` → `CountrySeeder` (NP row must exist or factory will create it).

**Note:** Each club factory call also creates an `Address` record, so running this seeder will produce 50 clubs and 50 addresses.

---

### PlayerSeeder

**File:** `database/seeders/PlayerSeeder.php`

**What it creates:** 50 random players via `Player::factory()->count(50)->create()`.

**Dependencies:** `PlayerFactory` → `CountrySeeder` (NP row must exist).

**Note:** Player factory sets `address_id = null` by default, so no address records are created.

---

### VenueSeeder

**File:** `database/seeders/VenueSeeder.php`

**What it creates:** 50 random venues via `Venue::factory()->count(50)->create()`.

**Dependencies:** `VenueFactory` → `AddressFactory` → `CountrySeeder`.

**Note:** Each venue factory call also creates an `Address` record, producing 50 venues and 50 addresses.

---

### TournamentSeeder

**File:** `database/seeders/TournamentSeeder.php`

**What it creates:** Nothing — `run()` is empty (stub).

**Note:** Not yet implemented. Tournament factory (`TournamentFactory`) is available for use in tests.

---

## Seeder call order and dependency summary

```
CountrySeeder           (no deps)
BeltRankSeeder          (no deps)
TournamentRuleSetSeeder (no deps)
RoleSeeder              (no deps — requires Spatie migration)
DefaultAdminSeeder      (requires RoleSeeder)

# Dev-only (manual):
ClubSeeder              (requires CountrySeeder or Country NP row)
PlayerSeeder            (requires CountrySeeder or Country NP row)
VenueSeeder             (requires CountrySeeder or Country NP row)
TournamentSeeder        (stub — nothing to run)
```
