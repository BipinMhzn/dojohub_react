# Model Factories

All factory files live in `database/factories/`. Every factory extends `Illuminate\Database\Eloquent\Factories\Factory` and is resolved automatically by Laravel's factory discovery convention.

Factories that need a `Country` record (players, referees, addresses) call `Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal'])` in their `definition()`, so a valid country row is guaranteed without requiring `CountrySeeder` to have run first.

---

## AddressFactory

**Model:** `App\System\Address\Models\Address`

**File:** `database/factories/AddressFactory.php`

### definition()

| Field | Value |
|---|---|
| `address_line_1` | `faker->streetAddress()` |
| `address_line_2` | `null` |
| `city` | `faker->city()` |
| `state` | `faker->state()` |
| `postal_code` | `null` |
| `country_code` | `'NP'` (ensures `NP` row exists via `firstOrCreate`) |
| `latitude` | `null` |
| `longitude` | `null` |

**Dependencies:** `Country` (NP) — created if missing.

**State methods:** none.

---

## ClubFactory

**Model:** `App\System\Club\Models\Club`

**File:** `database/factories/ClubFactory.php`

### definition()

| Field | Value |
|---|---|
| `name` | `faker->company()` |
| `abbreviation` | `strtoupper(faker->bothify('???'))` — 3-char uppercase string |
| `address_id` | `Address::factory()` — creates a new Address |
| `phone_number` | `null` |

**Dependencies:** `AddressFactory` (via `Address::factory()`).

### State methods

| Method | Effect |
|---|---|
| `withoutAddress()` | Sets `address_id` to `null` — creates a club with no physical address |

---

## ClubPlayerFactory

**Model:** `App\System\ClubPlayer\Models\ClubPlayer`

**File:** `database/factories/ClubPlayerFactory.php`

Represents a player's club membership record (`clubs_players` table).

### definition()

| Field | Value |
|---|---|
| `club_id` | `Club::factory()` — creates a new Club |
| `player_id` | `Player::factory()` — creates a new Player |
| `belt_rank_id` | `null` |
| `grade_certificate` | `null` |
| `started_at` | `faker->dateTimeBetween('-3 years', '-1 month')` |
| `ended_at` | `null` (active membership) |

**Dependencies:** `ClubFactory`, `PlayerFactory`.

### State methods

| Method | Effect |
|---|---|
| `ended()` | Sets `ended_at` to a datetime between `started_at` and now — marks membership as concluded |

---

## ClubCoachFactory

**Model:** `App\System\ClubCoach\Models\ClubCoach`

**File:** `database/factories/ClubCoachFactory.php`

Represents a coach's club affiliation record (`clubs_coaches` table).

### definition()

| Field | Value |
|---|---|
| `club_id` | `Club::factory()` |
| `coach_id` | `Coach::factory()` |
| `started_at` | `faker->dateTimeBetween('-3 years', '-1 month')` |
| `ended_at` | `null` (active) |

**Dependencies:** `ClubFactory`, `CoachFactory`.

### State methods

| Method | Effect |
|---|---|
| `ended()` | Sets `ended_at` to a datetime between `started_at` and now |

---

## CoachFactory

**Model:** `App\System\Coach\Models\Coach`

**File:** `database/factories/CoachFactory.php`

### definition()

| Field | Value |
|---|---|
| `user_id` | `null` (not linked to a system account by default) |
| `full_name` | `faker->name()` |
| `email` | `faker->safeEmail()` |
| `phone_number` | `faker->numerify('+1##########')` |

**Dependencies:** none.

**State methods:** none.

---

## PlayerFactory

**Model:** `App\System\Player\Models\Player`

**File:** `database/factories/PlayerFactory.php`

### definition()

| Field | Value |
|---|---|
| `name` | `faker->name()` |
| `gender` | random `PlayerGenderEnum` case value |
| `dob` | `faker->date()` |
| `weight` | `faker->randomFloat(2, 30, 150)` kg |
| `nationality_code` | `'NP'` (ensures NP row exists via `firstOrCreate`) |
| `current_belt_rank_id` | `null` |
| `address_id` | `null` |
| `email` | `faker->optional()->safeEmail()` |
| `profile_photo_url` | `null` |
| `phone_number` | `faker->optional()->numerify('+977 ##########')` |

**Dependencies:** `Country` (NP) — created if missing.

**State methods:** none.

---

## RefereeFactory

**Model:** `App\System\Referee\Models\Referee`

**File:** `database/factories/RefereeFactory.php`

### definition()

| Field | Value |
|---|---|
| `user_id` | `null` (not linked to a system account by default) |
| `full_name` | `faker->name()` |
| `nationality_code` | `'NP'` (ensures NP row exists via `firstOrCreate`) |
| `certification_level` | random `RefereeCertificationLevelEnum` case value |

**Dependencies:** `Country` (NP) — created if missing.

**State methods:** none.

---

## TournamentFactory

**Model:** `App\System\Tournament\Models\Tournament`

**File:** `database/factories/TournamentFactory.php`

### definition()

| Field | Value |
|---|---|
| `name` | `faker->sentence(3)` |
| `level` | random `TournamentLevelEnum` case |
| `status` | `TournamentStatusEnum::Scheduled` |
| `start_at` | `faker->dateTimeBetween('+1 week', '+3 months')` |
| `venue_id` | `Venue::factory()` — creates a new Venue |

**Dependencies:** `VenueFactory` (which in turn creates an `AddressFactory`).

### State methods

| Method | Signature | Effect |
|---|---|---|
| `withRuleSet()` | `withRuleSet()` | Sets `rule_set_id` to `TournamentRuleSet::first()?->id` — requires at least one rule set to already exist (e.g. from `TournamentRuleSetSeeder`) |
| `withTimezone()` | `withTimezone(string $timezone = 'Asia/Kathmandu')` | Sets `timezone` to the given IANA string |
| `inProgress()` | `inProgress()` | Sets `status = InProgress`, `start_at = now()->subDay()` |
| `completed()` | `completed()` | Sets `status = Completed`, `start_at = now()->subWeek()`, `end_at = now()->subDays(5)` |

---

## UserFactory

**Model:** `App\System\User\Models\User`

**File:** `database/factories/UserFactory.php`

### definition()

| Field | Value |
|---|---|
| `name` | `fake()->name()` |
| `email` | `fake()->unique()->safeEmail()` |
| `email_verified_at` | `now()` |
| `password` | `Hash::make('password')` — cached in `static $password` so hashing only happens once per test run |
| `remember_token` | `Str::random(10)` |

**Dependencies:** none.

### State methods

| Method | Effect |
|---|---|
| `unverified()` | Sets `email_verified_at` to `null` |

---

## VenueFactory

**Model:** `App\System\Venue\Models\Venue`

**File:** `database/factories/VenueFactory.php`

### definition()

| Field | Value |
|---|---|
| `name` | `faker->company()` |
| `address_id` | `Address::factory()` — creates a new Address |

**Dependencies:** `AddressFactory`.

**State methods:** none.

---

## VenueMatFactory

**Model:** `App\System\VenueMat\Models\VenueMat`

**File:** `database/factories/VenueMatFactory.php`

### definition()

| Field | Value |
|---|---|
| `venue_id` | `Venue::factory()` — creates a new Venue |
| `mat_number` | `faker->numberBetween(1, 20)` |
| `display_name` | `null` |

**Dependencies:** `VenueFactory`.

### State methods

| Method | Effect |
|---|---|
| `withDisplayName()` | Sets `display_name` to `'Tatami ' . faker->randomLetter()` |

---

## Factory dependency graph

```
AddressFactory
    └── used by: ClubFactory, VenueFactory

VenueFactory (→ AddressFactory)
    └── used by: TournamentFactory, VenueMatFactory

ClubFactory (→ AddressFactory)
    └── used by: ClubPlayerFactory, ClubCoachFactory

PlayerFactory (→ Country:NP)
    └── used by: ClubPlayerFactory

CoachFactory
    └── used by: ClubCoachFactory

RefereeFactory (→ Country:NP)

TournamentFactory (→ VenueFactory)

UserFactory
```
