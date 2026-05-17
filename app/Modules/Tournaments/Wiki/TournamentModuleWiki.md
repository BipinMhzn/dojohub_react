# Tournaments Module Wiki

## Overview

The Tournaments module manages the full lifecycle of a judo tournament:

```
Tournament
  └── Tournament Categories  (gender + age + weight divisions)
          └── Tournament Registrations  (player enrolled into a category)
          └── Tournament Bracket  (bracket structure for that division)
                    └── Tournament Matches  (individual fights)
                              └── Tournament Match Competitors  (per-match athlete record)
                    └── Tournament Results  (final positions)
```

---

## Module Location

```
app/Modules/Tournaments/
├── Controllers/
│   ├── TournamentsController.php
│   ├── CategoriesController.php
│   ├── RegistrationsController.php
│   └── MatchesController.php
├── Interfaces/
│   ├── TournamentInterface.php
│   ├── CategoryInterface.php
│   ├── RegistrationInterface.php
│   ├── BracketInterface.php
│   ├── MatchInterface.php
│   └── BracketServiceInterface.php
├── Models/
│   ├── Tournament.php
│   ├── TournamentCategory.php
│   ├── TournamentRegistration.php
│   ├── TournamentBracket.php
│   ├── TournamentMatch.php
│   ├── TournamentMatchCompetitor.php
│   ├── TournamentResult.php
│   ├── Player.php
│   ├── Club.php
│   └── Venue.php
├── Repositories/
│   ├── TournamentRepository.php
│   ├── CategoryRepository.php
│   ├── RegistrationRepository.php
│   ├── BracketRepository.php
│   └── MatchRepository.php
├── Requests/
│   ├── TournamentCreateRequest.php
│   ├── TournamentUpdateRequest.php
│   ├── CategoryCreateRequest.php
│   ├── CategoryUpdateRequest.php
│   ├── TournamentRegistrationCreateRequest.php
│   ├── TournamentRegistrationUpdateRequest.php
│   ├── BracketGenerateRequest.php
│   ├── MatchResultRequest.php
│   └── MatchScheduleRequest.php
├── Services/
│   └── BracketService.php
├── Filters/
│   └── TournamentsFilter.php
├── Exceptions/
│   └── PlayerAlreadyRegisteredException.php
├── Routes/
│   └── web.php
└── ServiceProvider.php
```

System-layer models live in `app/System/Tournament/Models/`. Module models in `app/Modules/Tournaments/Models/` are thin `final class X extends BaseModel {}` wrappers.

---

## Routes

All routes are prefixed with `/manage` and require `auth` + `verified` middleware.

```
GET    /manage/tournaments                                         tournaments.index
GET    /manage/tournaments/create                                  tournaments.create
POST   /manage/tournaments                                         tournaments.store
GET    /manage/tournaments/{tournament}/edit                       tournaments.edit
POST   /manage/tournaments/{tournament}                            tournaments.update
DELETE /manage/tournaments/{tournament}                            tournaments.destroy

GET    /manage/tournaments/{tournament}/categories                 tournaments.categories.index
GET    /manage/tournaments/{tournament}/categories/create          tournaments.categories.create
POST   /manage/tournaments/{tournament}/categories                 tournaments.categories.store
GET    /manage/tournaments/{tournament}/categories/{category}/edit  tournaments.categories.edit
PUT    /manage/tournaments/{tournament}/categories/{category}       tournaments.categories.update
DELETE /manage/tournaments/{tournament}/categories/{category}       tournaments.categories.destroy

GET    /manage/tournaments/{tournament}/categories/{category}/registrations              tournaments.categories.registrations.index
POST   /manage/tournaments/{tournament}/categories/{category}/registrations              tournaments.categories.registrations.store
DELETE /manage/tournaments/{tournament}/categories/{category}/registrations/{reg}        tournaments.categories.registrations.destroy

GET    /manage/tournaments/{tournament}/categories/{category}/matches                    tournaments.categories.matches.index
POST   /manage/tournaments/{tournament}/categories/{category}/matches/generate           tournaments.categories.matches.generate
PATCH  /manage/tournaments/{tournament}/categories/{category}/matches/{match}/result     tournaments.categories.matches.update-result
PATCH  /manage/tournaments/{tournament}/categories/{category}/matches/{match}/schedule   tournaments.categories.matches.update-schedule
```

---

## Enums

All enums are in `app/System/Tournament/Foundation/Enums/`.

| Enum | Values |
|------|--------|
| `TournamentLevelEnum` | Local=1, Regional=2, National=3, International=4 |
| `TournamentStatusEnum` | Scheduled=0, InProgress=1, Completed=2, Cancelled=3 |
| `CategoryStatusEnum` | Pending=0, Seeding=1, Active=2, Completed=3 |
| `RegistrationStatusEnum` | Registered=0, Confirmed=1, Withdrawn=2 |
| `AgeCategoryEnum` | Preparation=0 … Veterans=9 |
| `WeightCategoryEnum` | Female48=0 … Female78Plus=13 |
| `BracketTypeEnum` | SingleElimination=0, DoubleElimination=1, PoolFinal=2, Repechage=3 |
| `BracketStatusEnum` | Pending=0, Active=1, Completed=2 |
| `BracketSectionEnum` | Main=0, Repechage=1, Bronze=2, Final=3 |
| `SeedingMethodEnum` | Random=0, Manual=1, ByRank=2 |
| `MatchStatusEnum` | Pending=0, Active=1, Completed=2 |
| `CompetitorPositionEnum` | White=0, Blue=1 |
| `CompetitorResultEnum` | Pending=0, Win=1, Loss=2, WalkoverWin=3, Disqualified=4 |

> `TournamentTypeEnum` is kept for backward compatibility but is no longer used on the Tournament model. Use `BracketTypeEnum` for `tournament_brackets.type`.

---

## System Models

### `Tournament`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| name | string | |
| description | text | nullable |
| logo_url | string | nullable |
| level | tinyint | `TournamentLevelEnum` |
| start_at | datetime | |
| end_at | datetime | nullable |
| status | tinyint | `TournamentStatusEnum` |
| venue_id | ulid FK | → venues.id |

**Relationships**: `venue()`, `categories()`

---

### `TournamentCategory`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| tournament_id | ulid FK | → tournaments.id (cascade) |
| name | string | nullable — auto-generated from enums if null |
| gender | tinyint | `PlayerGenderEnum` |
| age_category | tinyint | `AgeCategoryEnum` |
| min_age / max_age | tinyint | Set from enum defaults, adjustable |
| weight_category | tinyint | `WeightCategoryEnum` |
| min_weight / max_weight | int | Set from enum defaults, adjustable |
| status | tinyint | `CategoryStatusEnum` |

**Relationships**: `tournament()`, `registrations()`, `brackets()`

**Unique constraint**: `(tournament_id, gender, age_category, weight_category)` — one division per tournament.

---

### `TournamentRegistration`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| tournament_category_id | ulid FK | → tournament_categories.id (restrict) |
| club_id | ulid FK | → clubs.id (restrict) |
| player_id | ulid FK | → players.id (restrict) |
| player_age | tinyint | Snapshot at registration time |
| player_weight | decimal(5,2) | Snapshot at registration time |
| status | tinyint | `RegistrationStatusEnum` |

**Unique constraint**: `(tournament_category_id, player_id)` — one entry per player per category.

**Relationships**: `category()`, `club()`, `player()`

---

### `TournamentBracket`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| tournament_category_id | ulid FK | → tournament_categories.id (cascade) |
| type | tinyint | `BracketTypeEnum` |
| seeding_method | tinyint | `SeedingMethodEnum` |
| competitor_count | tinyint | Set on generation |
| round_count | tinyint | Set on generation |
| status | tinyint | `BracketStatusEnum` |
| generated_at | datetime | nullable |

**Relationships**: `category()`, `matches()`

---

### `TournamentMatch`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| bracket_id | ulid FK | → tournament_brackets.id (cascade) |
| bracket_section | tinyint | `BracketSectionEnum` |
| round_number | tinyint | |
| round_name | string(50) | Display label |
| match_number | tinyint | Position within round (1-based) |
| mat_number | tinyint | nullable — tatami assignment |
| scheduled_at | datetime | nullable |
| completed_at | datetime | nullable |
| status | tinyint | `MatchStatusEnum` |
| winner_id | ulid FK | → players.id (set null) |
| winner_next_match_id | ulid FK | → tournament_matches.id (self-ref, set null) |
| winner_next_position | tinyint | 1 or 2 — slot in next match |
| loser_next_match_id | ulid FK | → tournament_matches.id (self-ref, set null) |
| loser_next_position | tinyint | 1 or 2 |
| notes | text | nullable |

**Relationships**: `bracket()`, `competitors()`, `winner()`, `winnerNextMatch()`, `loserNextMatch()`

> Players are NOT stored directly on this table. They are in `TournamentMatchCompetitor` via `registration_id`.
> `winner_id` is a denormalised shortcut to `players.id` for fast lookup.

---

### `TournamentMatchCompetitor`

Per-match athlete record linking a registration to a match.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| match_id | ulid FK | → tournament_matches.id (cascade) |
| registration_id | ulid FK | → tournament_registrations.id (restrict) |
| position | tinyint | `CompetitorPositionEnum` (0=White, 1=Blue) |
| seed | tinyint | nullable |
| score | tinyint | nullable — Waza-ari count (0–2) |
| penalties | tinyint | nullable — Shido count (0–3) |
| result | tinyint | `CompetitorResultEnum` default 0 (Pending) |

**Unique constraints**: `(match_id, position)`, `(match_id, registration_id)`

**Relationships**: `match()`, `registration()`

---

### `TournamentResult`

Final finishing position per competitor per category.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| registration_id | ulid FK | → tournament_registrations.id (restrict) |
| position | tinyint | 1=Gold, 2=Silver, 3=Bronze, 5=5th, 7=7th |

**Unique**: `(registration_id)`

---

## Controllers

### `TournamentsController`

Standard CRUD for the tournament container. No category fields — categories are managed separately.

| Method | Route | Description |
|--------|-------|-------------|
| `index` | GET /tournaments | Paginated list with filters |
| `create` | GET /tournaments/create | New tournament form |
| `store` | POST /tournaments | Create + logo upload |
| `edit` | GET /tournaments/{id}/edit | Edit form |
| `update` | POST /tournaments/{id} | Update + logo upload |
| `destroy` | DELETE /tournaments/{id} | Soft-delete |

---

### `CategoriesController`

Manages competition divisions under a tournament.

| Method | Route | Description |
|--------|-------|-------------|
| `index` | GET /tournaments/{t}/categories | List categories with registration counts |
| `create` | GET /tournaments/{t}/categories/create | New category form |
| `store` | POST /tournaments/{t}/categories | Create category with status=Pending |
| `edit` | GET /tournaments/{t}/categories/{c}/edit | Edit form |
| `update` | PUT /tournaments/{t}/categories/{c} | Update |
| `destroy` | DELETE /tournaments/{t}/categories/{c} | Soft-delete |

---

### `RegistrationsController`

Manages player registrations per category.

| Method | Route | Description |
|--------|-------|-------------|
| `index` | GET …/registrations | List registrations + eligible players (if Pending) |
| `store` | POST …/registrations | Register player: snapshot age/weight, check eligibility |
| `destroy` | DELETE …/registrations/{r} | Remove registration |

Registration is restricted when `category.status !== Pending`.
`getPlayerList()` filters by `dob`, `weight`, and `gender` matching the category criteria, excluding already-registered players.

---

### `MatchesController`

Manages bracket generation and match results per category.

| Method | Route | Description |
|--------|-------|-------------|
| `index` | GET …/matches | Show bracket structure |
| `generate` | POST …/matches/generate | Create bracket + matches |
| `updateResult` | PATCH …/matches/{m}/result | Record winner + scores |
| `updateSchedule` | PATCH …/matches/{m}/schedule | Set scheduled_at |

---

## BracketService

`App\Modules\Tournaments\Services\BracketService` implements `BracketServiceInterface`.

### `generateBracket(TournamentCategory $category, TournamentBracket $bracket)`
1. Calls `seedRegistrations()` — top 3 registrations (by creation order) become seeds 1–3; rest are shuffled
2. Dispatches to `generateSingleElimination()` based on bracket type (Double Elimination currently also uses single elimination as a placeholder)
3. Updates `bracket.competitor_count`, `bracket.round_count`, `bracket.status = Active`, `bracket.generated_at`

### `generateSingleElimination()`
1. Calculates `bracketSize = nextPowerOfTwo(playerCount)`
2. Creates first-round `TournamentMatch` records with `TournamentMatchCompetitor` rows (White=seed1, Blue=seed2)
3. Bye matches (no Blue competitor) are auto-completed with `WalkoverWin` for White
4. Subsequent rounds are created as empty TBD matches with `winner_next_match_id` links between rounds

### `recordMatchResult()`
1. Finds White and Blue competitors for the match
2. Sets `result = Win/Loss` on each competitor
3. Updates match: `winner_id`, `status = Completed`, `completed_at`, `notes`
4. Creates a new `TournamentMatchCompetitor` in the winner's next match
5. If `loser_next_match_id` exists, creates a competitor row in the repechage match

### `getBracketStructure(TournamentBracket $bracket)`
Returns a structured array grouped by `bracket_section` name and `round_number`:
```php
[
    'id' => '...',
    'type' => 0,
    'status' => 1,
    'sections' => [
        'Main' => [
            ['round_number' => 1, 'round_name' => 'Round of 8', 'matches' => [...]],
            ['round_number' => 2, 'round_name' => 'Semifinal', 'matches' => [...]],
        ],
        'Final' => [
            ['round_number' => 3, 'round_name' => 'Final', 'matches' => [...]],
        ],
    ],
]
```

---

## Frontend

### Page Components

```
resources/js/pages/tournaments/
├── index.tsx                      # Tournament list (types/enums + list page)
├── create.tsx                     # Create tournament form (no category fields)
├── edit.tsx                       # Edit tournament form
├── categories/
│   ├── index.tsx                  # Category cards with registration counts
│   ├── create.tsx                 # Add category form
│   └── edit.tsx                   # Edit category form
├── registrations/
│   └── index.tsx                  # Player registrations for a category
├── matches/
│   └── index.tsx                  # Bracket view + result recording
└── partials/
    ├── columns.tsx                # TanStack columns for tournament list
    ├── tournament-header.tsx      # Tournament info card
    ├── tournament-tabs.tsx        # Categories navigation tab
    └── status-badge.tsx           # Tournament status badge
```

### Type Exports from `index.tsx`

```typescript
// Enums
TournamentStatus, TournamentLevel, CategoryStatus, RegistrationStatus,
Gender, AgeCategory, WeightCategory, BracketType, BracketStatus, BracketSection,
MatchStatus, CompetitorPosition, CompetitorResult

// Types
Tournament, TournamentCategory, TournamentRegistration,
MatchCompetitor, BracketMatch, BracketRound, BracketStructure
```

---

## User Flows

### 1. Create Tournament
1. Navigate to `/manage/tournaments/create`
2. Fill: name, level, start date, (optional) end date, venue, (optional) logo + description
3. Tournament is created with `status = Scheduled`

### 2. Add Categories
1. From tournament list, click tournament name → `/manage/tournaments/{id}/categories`
2. Click "Add Category" → fill gender, age category (auto-fills min/max age), weight category (auto-fills min/max weight)
3. Category created with `status = Pending`

### 3. Register Players
1. From category card, click dropdown → "Registrations"
2. Player list filtered by: age range (calculated from DOB), weight range, gender matching the category
3. Already-registered players are excluded from the dropdown
4. On store: `player_age` and `player_weight` are snapshotted from the player record

### 4. Generate Bracket
1. From category card, click dropdown → "Matches"
2. Select bracket type and seeding method, click "Generate Bracket"
3. `BracketService` creates `TournamentBracket`, seeds registrations, creates `TournamentMatch` and `TournamentMatchCompetitor` records
4. Bye matches (odd number of players) are auto-completed

### 5. Record Match Results
1. On the matches page, click a match card (only available if both competitors are present and match not completed)
2. Select winner (White or Blue button)
3. Click "Record Result"
4. Winner advances to `winner_next_match_id` as a new competitor; loser advances to `loser_next_match_id` for repechage

---

## ServiceProvider Bindings

```php
TournamentInterface::class  → TournamentRepository::class
CategoryInterface::class    → CategoryRepository::class
RegistrationInterface::class → RegistrationRepository::class
BracketInterface::class     → BracketRepository::class
MatchInterface::class       → MatchRepository::class
BracketServiceInterface::class → BracketService::class
```
