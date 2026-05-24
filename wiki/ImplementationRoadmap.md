# DojoHub — Implementation Roadmap

> **Database status**: All 27 domain migrations are implemented and clean (`migrate:fresh` passes).
> Packages installed: `owen-it/laravel-auditing`, `spatie/laravel-permission`.
> `AppBaseModel` implements `Auditable` with `EditorLogs` columns excluded from audit diffs.
>
> This document tracks all remaining application-layer work, organised by module.
> Complete phases in the order listed within each module. Cross-module phases can run in parallel unless a dependency is noted.

---

## Table of Contents

1. [Global Phase 1 — Enum Hygiene ✅](#global-phase-1--enum-hygiene-)
2. [Global Phase 2 — Reference Data Models & Seeders ✅](#global-phase-2--reference-data-models--seeders-)
3. [Global Phase 3 — RBAC Setup ✅](#global-phase-3--rbac-setup-)
4. [Module: Venues ✅](#module-venues-)
5. [Module: Clubs ✅](#module-clubs-)
6. [Module: Players ✅](#module-players-)
7. [Module: Referees (New) ✅](#module-referees-new)
8. [Module: Coaches (New) ✅](#module-coaches-new-)
9. [Module: Tournaments ✅](#module-tournaments-)
10. [Module: Tournament Categories ✅](#module-tournament-categories-)
11. [Module: Tournament Registrations](#module-tournament-registrations)
12. [Module: Tournament Brackets & Pools](#module-tournament-brackets--pools)
13. [Module: Tournament Matches](#module-tournament-matches)
14. [Module: Tournament Match Events (New)](#module-tournament-match-events-new)
15. [Module: Tournament Match Referees (New)](#module-tournament-match-referees-new)
16. [Module: Tournament Weigh-ins (New)](#module-tournament-weigh-ins-new)
17. [Module: Tournament Registration Coaches (New)](#module-tournament-registration-coaches-new)
18. [Module: Financial Tables (New)](#module-financial-tables-new)
19. [Global Phase 4 — Factories, Seeders & Test Suite](#global-phase-4--factories-seeders--test-suite)

---

## Conventions

- Run `vendor/bin/pint --dirty` after every PHP change.
- After each module phase: `php artisan migrate:fresh --seed && php artisan test`.
- When editing schema columns, update `wiki/Database.md` in the same commit.
- When editing enums, update `wiki/Enums.md` in the same commit.
- Every new module needs: System model, Module model, Interface, Repository, Controller, Requests, Filter, Routes, ServiceProvider, frontend pages, factory, feature tests.

---

## Global Phase 1 — Enum Hygiene ✅

**No dependencies. Complete before any module work.**

### 1.1 Delete `TournamentTypeEnum`

- Delete `app/System/Tournament/Foundation/Enums/TournamentTypeEnum.php`
- `grep -rn TournamentTypeEnum app/ resources/` — remove every reference
- Update `wiki/Enums.md` to remove the entry

### 1.2 Rename `BeltRankEnum` → superseded by `belt_ranks` table

- `app/System/ClubPlayer/Foundation/Enums/BeltRankEnum.php` used the old colour-based belt model (WHITE=0 … BLACK=7). The fresh schema replaces this with the `belt_ranks` reference table (Rokkyu=6K → Judan=10D).
- Delete `BeltRankEnum.php`
- `grep -rn BeltRankEnum app/ resources/` — replace every reference with a lookup against the `BeltRank` model (added in Global Phase 2)

### 1.3 Fix `PlayerGenderEnum` case style

- Cases are `UPPER_CASE` — rename to TitleCase per project convention
- `MALE → Male`, `FEMALE → Female`
- `grep -rn PlayerGenderEnum app/ resources/` — update every `::MALE` / `::FEMALE` reference
- Update `wiki/Enums.md`

### 1.4 Rename `BracketSectionEnum::Final → GoldFinal`

- `app/System/Tournament/Foundation/Enums/BracketSectionEnum.php`
- `grep -rn BracketSectionEnum::Final app/ resources/` — update every reference
- Update `wiki/Enums.md`

### 1.5 Add missing enums (new from fresh architecture)

Create the following files under `app/System/Tournament/Foundation/Enums/`:

| File | Cases |
|------|-------|
| `MatchOutcomeEnum.php` | Ippon=0, WazaAri=1, Decision=2, HansokuMake=3, Walkover=4, Bye=5, GoldenScore=6 |
| `MatchEventTypeEnum.php` | Ippon=0, WazaAri=1, Shido=2, HansokuMake=3, HansokuMakeLight=4, Kinsa=5, Note=6 |

Create under `app/System/Referee/Foundation/Enums/`:

| File | Cases |
|------|-------|
| `RefereeRoleEnum.php` | Center=0, Side=1, Video=2 |
| `RefereeCertificationLevelEnum.php` | Club=0, National=1, Continental=2, International=3 |

Create under `app/System/Tournament/Foundation/Enums/` (financial):

| File | Cases |
|------|-------|
| `PaymentMethodEnum.php` | Cash=0, Bank=1, Online=2 |

Create under `app/System/BeltRank/Foundation/Enums/`:

| File | Cases |
|------|-------|
| `BeltRankKindEnum.php` | Kyu=0, Dan=1 |

Update `wiki/Enums.md` with all new entries.

---

## Global Phase 2 — Reference Data Models & Seeders ✅

**Depends on**: Global Phase 1 (BeltRankEnum deleted).

These tables are seeded, not user-managed CRUD. No web routes needed.

### 2.1 Countries

- `app/System/Country/Models/Country.php` — extends `Illuminate\Database\Eloquent\Model` directly (no `AppBaseModel` — no soft-delete, no audit). `protected $primaryKey = 'code'`; `public $incrementing = false`; `protected $keyType = 'string'`. Fillable: `code`, `name`.
- `app/Modules/Countries/Models/Country.php` — thin extension
- `database/seeders/CountrySeeder.php` — insert all ISO 3166-1 alpha-2 countries using `Country::upsert()`
- Wire `CountrySeeder` into `DatabaseSeeder` as step 1 (before anything with `country_code` FK)

### 2.2 Belt Ranks

- `app/System/BeltRank/Models/BeltRank.php` — extends `Model` directly. `$incrementing = false`; `$keyType = 'string'` (ULID). Fillable: `id`, `code`, `name`, `kind`, `color`, `sort_order`. Cast `kind → BeltRankKindEnum`.
- `app/Modules/BeltRanks/Models/BeltRank.php`
- `database/seeders/BeltRankSeeder.php` — seed 16 standard Judo belts in ascending `sort_order`:

| sort_order | code | name | kind | color |
|-----------|------|------|------|-------|
| 1 | 6K | Rokkyu | Kyu | White |
| 2 | 5K | Gokyu | Kyu | Yellow |
| 3 | 4K | Yonkyu | Kyu | Orange |
| 4 | 3K | Sankyu | Kyu | Green |
| 5 | 2K | Nikyu | Kyu | Blue |
| 6 | 1K | Ikkyu | Kyu | Brown |
| 7 | 1D | Shodan | Dan | Black |
| 8 | 2D | Nidan | Dan | Black |
| 9 | 3D | Sandan | Dan | Black |
| 10 | 4D | Yondan | Dan | Black |
| 11 | 5D | Godan | Dan | Black |
| 12 | 6D | Rokudan | Dan | Red/White |
| 13 | 7D | Shichidan | Dan | Red/White |
| 14 | 8D | Hachidan | Dan | Red/White |
| 15 | 9D | Kudan | Dan | Red |
| 16 | 10D | Judan | Dan | Red |

- Wire `BeltRankSeeder` into `DatabaseSeeder` after `CountrySeeder`

### 2.3 Tournament Rule Sets

- `app/System/TournamentRuleSet/Models/TournamentRuleSet.php` — extends `AppBaseModel`. Fillable: `name`, `max_waza_ari`, `max_shido`, `match_duration_seconds`, `golden_score_seconds`.
- `app/Modules/TournamentRuleSets/Models/TournamentRuleSet.php`
- `database/seeders/TournamentRuleSetSeeder.php` — seed one row: `IJF 2024` (max_waza_ari=2, max_shido=3, match_duration_seconds=240, golden_score_seconds=null)
- Wire into `DatabaseSeeder` after `BeltRankSeeder`

---

## Global Phase 3 — RBAC Setup ✅

**Depends on**: spatie/laravel-permission already installed.

### 3.1 User Model — HasRoles

- Add `use Spatie\Permission\Traits\HasRoles;` to `app/System/User/Models/User.php`
- Publish spatie config: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-config"` — review `config/permission.php`, set correct `user_model`

### 3.2 Middleware Registration

In `bootstrap/app.php`, register the spatie middleware aliases:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

### 3.3 Role Seeder

- `database/seeders/RoleSeeder.php` — create 5 roles in order: `super_admin`, `tournament_manager`, `club_admin`, `referee`, `coach`
- Wire into `DatabaseSeeder` before `DefaultAdminSeeder`

### 3.4 Assign super_admin to Default Admin

- Update `DefaultAdminSeeder` to assign `super_admin` role to the seeded admin user after creation

---

## Module: Venues ✅

**Existing module. Depends on**: Global Phase 2 (countries seeded for address country_code).

### Phase V1 — Address System Model Update

Update `app/System/Address/Models/Address.php`:
- Fillable: replace `address` → `address_line_1`, add `address_line_2`, `postal_code`, `country_code`, `latitude`, `longitude`. Keep `city`, `state`.
- Add relationship: `country()` → `BelongsTo Country` (using `country_code`)
- Casts: `latitude` / `longitude` as float if needed

Update all `address`-related form fields site-wide from `address` to `address_line_1`.

### Phase V2 — Venue System Model Update

Update `app/System/Venue/Models/Venue.php`:
- Remove `mat_count` from fillable and any accessors referencing it
- Add relationship: `mats()` → `HasMany VenueMat`

### Phase V3 — VenueMat — New System Model

- `app/System/VenueMat/Models/VenueMat.php` — extends `AppBaseModel`. Fillable: `venue_id`, `mat_number`, `display_name`.
- `app/Modules/Venues/Models/VenueMat.php`

### Phase V4 — VenueMat Sub-resource

Add to `Venues` module:

- `VenueMatInterface` + `VenueMatRepository` — injected into `VenueRepository` or as a standalone
- `VenueMatController` (nested under Venue):
  - `index` — list mats for a venue
  - `store` — create mat
  - `update` — rename display_name
  - `destroy` — soft-delete mat
- Routes: `venues.mats.index`, `venues.mats.store`, `venues.mats.update`, `venues.mats.destroy` (nested under `/manage/venues/{venue}/mats`)
- Frontend: `resources/js/pages/venues/mats/index.tsx` — inline table within the venue detail/edit view
- Factory: `VenueMatFactory`
- Tests: CRUD + unique constraint (same mat_number per venue)

### Phase V5 — Address Form Update

Update `resources/js/pages/venues/{create,edit}.tsx`:
- Split `address` field into `address_line_1` + optional `address_line_2`
- Replace free-text `country` with `country_code` dropdown (populated from countries list)
- Add `postal_code`, optional `latitude`/`longitude` fields
- Update `VenueCreateRequest` / `VenueUpdateRequest` validation rules

Update `wiki/VenuesModule.md` to reflect all changes.

---

## Module: Clubs ✅

**Existing module. Depends on**: Phase V1 (Address model updated), Global Phase 2 (BeltRankEnum removed).

### Phase C1 — Club System Model Update

Update `app/System/Club/Models/Club.php`:
- `address_id` is now nullable — document this in fillable comment
- No structural relationship changes

### Phase C2 — Club Requests Update

- `ClubCreateRequest` / `ClubUpdateRequest`: make all address fields `nullable` (address is no longer required)
- Guard address creation in repository: only create an `Address` record if at least `address_line_1` is provided

### Phase C3 — Club Repository Update

Update `VenueRepository`-pattern methods:
- `createClub`: conditionally create Address only when address fields are present
- `updateClub`: handle creating a new address if none exists yet, or updating the existing one
- `deleteClub`: only soft-delete address if `$club->address_id` is not null

Update `wiki/ClubsModule.md`.

### Phase C4 — ClubPlayer (Belt Rank FK)

Update `app/System/ClubPlayer/Models/ClubPlayer.php`:
- Remove `belt_rank` tinyint from fillable
- Add `belt_rank_id` to fillable and cast as `BelongsTo BeltRank`
- Rename `grade` → `grade_certificate` in fillable
- Add relationship: `beltRank()` → `BelongsTo BeltRank`

Update `ClubPlayerCreateRequest` / `ClubPlayerUpdateRequest`:
- Replace `belt_rank` integer with `belt_rank_id` (nullable, exists in `belt_ranks`)
- Rename `grade` → `grade_certificate`

Update frontend club players form: belt rank becomes a dropdown seeded from `belt_ranks`, grade becomes `grade_certificate` text input.

Update `ClubPlayerFactory`: use random `BeltRank::inRandomOrder()->first()->id` for `belt_rank_id`.

Update `wiki/ClubPlayers.md`.

---

## Module: Players ✅

**Existing module. Depends on**: Global Phase 2 (countries, belt_ranks seeded), Phase V1 (Address model updated).

### Phase P1 — Player System Model Update

Update `app/System/Player/Models/Player.php`:
- Remove `nationality string` from fillable
- Add `nationality_code char(2)` to fillable — relationship `country()` → `BelongsTo Country`
- Add `current_belt_rank_id` to fillable — relationship `currentBeltRank()` → `BelongsTo BeltRank`
- Add `address_id` to fillable — relationship `address()` → `BelongsTo Address`
- Cast `gender → PlayerGenderEnum` (after case rename in Global Phase 1)

### Phase P2 — Player Repository Update

- Handle optional address creation (same pattern as Clubs Phase C3)
- `onBeforeResult` and `getById` override: eager-load `country`, `currentBeltRank`, `address`

### Phase P3 — Player Requests Update

- `PlayerCreateRequest` / `PlayerUpdateRequest`:
  - Replace `nationality` → `nationality_code` (nullable, exists in `countries`)
  - Add `current_belt_rank_id` (nullable, exists in `belt_ranks`)
  - Add optional address fields (`address_line_1`, `city`, `country_code`, etc.)

### Phase P4 — Player Frontend Update

- Replace `nationality` text input with `nationality_code` country dropdown
- Add `current_belt_rank_id` belt rank dropdown
- Add optional collapsible address section
- Update TypeScript types: `Player.nationality_code`, `Player.current_belt_rank_id`, `Player.address`

Update `wiki/PlayersModule.md`.

---

## Module: Referees (New) ✅

**Depends on**: Global Phase 1 (RefereeCertificationLevelEnum, RefereeRoleEnum created), Global Phase 2 (countries seeded).

### Phase REF1 — System Model

- `app/System/Referee/Models/Referee.php` — extends `AppBaseModel`. Fillable: `user_id`, `full_name`, `nationality_code`, `certification_level`. Casts: `certification_level → RefereeCertificationLevelEnum`. Relationships: `user()` → `BelongsTo User`, `country()` → `BelongsTo Country`, `matchAssignments()` → `HasMany TournamentMatchReferee`.

### Phase REF2 — Full Module Scaffold

Generate with `php artisan base-system:generate Referee --web`, then fill in:

- `app/Modules/Referees/Models/Referee.php`
- `app/Modules/Referees/Interfaces/RefereeInterface.php` — declares `createReferee`, `updateReferee`, `deleteReferee`
- `app/Modules/Referees/Repositories/RefereeRepository.php`
- `app/Modules/Referees/Controllers/RefereeController.php` — standard `index`, `create`, `store`, `edit`, `update`, `destroy`
- `app/Modules/Referees/Requests/RefereeCreateRequest.php` / `RefereeUpdateRequest.php`
- `app/Modules/Referees/Filters/RefereeFilter.php` — filter by `full_name`, `certification_level`, `nationality_code`
- `app/Modules/Referees/Routes/web.php` — `manage/referees`, PUT for update (no file uploads)
- `app/Modules/Referees/ServiceProvider.php`

### Phase REF3 — Frontend

- `resources/js/pages/referees/index.tsx` — table: full_name, nationality_code, certification_level label
- `resources/js/pages/referees/create.tsx`
- `resources/js/pages/referees/edit.tsx`
- Dark mode required throughout

### Phase REF4 — Factory & Tests

- `database/factories/RefereeFactory.php`
- Feature tests: index, create, update, delete, filter by certification level

---

## Module: Coaches (New) ✅

**Depends on**: Clubs module (coach has optional `club_id` FK).

### Phase COA1 — System Model

- `app/System/Coach/Models/Coach.php` — extends `AppBaseModel`. Fillable: `user_id`, `full_name`, `club_id`. Relationships: `user()` → `BelongsTo User`, `club()` → `BelongsTo Club`.

### Phase COA2 — Full Module Scaffold

Same structure as Referees module:

- Interface declares `createCoach`, `updateCoach`, `deleteCoach`
- Filter: `full_name`, `club_id`
- Routes: `manage/coaches`, PUT for update

### Phase COA3 — Frontend

- `resources/js/pages/coaches/{index,create,edit}.tsx`
- Club dropdown on create/edit (optional)

### Phase COA4 — Factory & Tests

- `database/factories/CoachFactory.php`
- Feature tests: CRUD + optional club assignment

---

## Module: Tournaments ✅

**Existing module. Depends on**: Global Phase 2 (tournament_rule_sets seeded).

### Phase T1 — Tournament System Model Update

Update `app/System/Tournament/Models/Tournament.php`:
- Add `rule_set_id` to fillable — relationship `ruleSet()` → `BelongsTo TournamentRuleSet`
- Add `timezone` to fillable
- Casts: `level → TournamentLevelEnum`, `status → TournamentStatusEnum`
- Eager-load `ruleSet` in `onBeforeResult` and `getById` override

### Phase T2 — Tournament Requests Update

- `TournamentCreateRequest` / `TournamentUpdateRequest`:
  - Add `rule_set_id` (nullable, `exists:tournament_rule_sets,id`)
  - Add `timezone` (nullable, valid IANA timezone — use `timezone` Laravel validation rule)

### Phase T3 — Tournament Frontend Update

- `resources/js/pages/tournaments/{create,edit}.tsx`:
  - Add `rule_set_id` dropdown (show name, value is id)
  - Add `timezone` selector (IANA timezone list)
- Update TypeScript `Tournament` type: add `rule_set_id`, `timezone`, typed `level` and `status` enums

Update `wiki/TournamentsModule.md`.

---

## Module: Tournament Categories ✅

**Now a standalone module: `app/Modules/TournamentCategories/`. Depends on**: Module: Tournaments Phase T1.

### Phase TC1 — System Model Update

Update `app/System/Tournament/Models/TournamentCategory.php`:
- `min_weight` / `max_weight` now `decimal` — update casts to `'decimal:2'`
- Casts: `gender → PlayerGenderEnum`, `age_category → AgeCategoryEnum`, `weight_category → WeightCategoryEnum`, `status → CategoryStatusEnum`

### Phase TC2 — Requests Update

- `TournamentCategoryCreateRequest` / `TournamentCategoryUpdateRequest`:
  - Validate `min_weight` / `max_weight` as `numeric` with two decimal places, not integer
  - Add rule `min_weight <= max_weight` (matches DB CHECK)

### Phase TC3 — Frontend Update

- Weight fields: change input `type` from `number` (integer step) to `number` with `step="0.01"`
- Update TypeScript types: `min_weight: number`, `max_weight: number` (float)

---

## Module: Tournament Registrations

**Existing sub-feature. Depends on**: Module: Tournament Categories.

### Phase TR1 — System Model Update

Update `app/System/Tournament/Models/TournamentRegistration.php`:
- Cast `status → RegistrationStatusEnum`, `player_weight` as `'decimal:2'`
- Add relationships:
  - `weighIns()` → `HasMany TournamentWeighIn`
  - `coaches()` → `HasMany TournamentRegistrationCoach`
  - `payments()` → `HasMany TournamentRegistrationPayment`
  - `fee()` → `HasOneThrough` to category fee (or through category)

### Phase TR2 — Registration Status Flow

Add `confirm` and `withdraw` actions to the registrations controller:
- `POST manage/tournaments/{tournament}/categories/{category}/registrations/{registration}/confirm`
- `POST manage/tournaments/{tournament}/categories/{category}/registrations/{registration}/withdraw`
- Guard: only `tournament_manager` or `club_admin` may withdraw their own registrations

---

## Module: Tournament Brackets & Pools

**Existing (brackets). New (pools). Depends on**: Module: Tournament Registrations.

### Phase BR1 — Bracket System Model Update

Update `app/System/Tournament/Models/TournamentBracket.php`:
- Add `seeding_method` to fillable — cast to `SeedingMethodEnum`
- Casts: `type → BracketTypeEnum`, `status → BracketStatusEnum`
- Add relationship: `pools()` → `HasMany TournamentPool`

### Phase BR2 — TournamentPool — New System Model

- `app/System/Tournament/Models/TournamentPool.php` — extends `AppBaseModel`. Fillable: `bracket_id`, `name`. Relationships: `bracket()`, `competitors()` → `HasMany TournamentPoolCompetitor`, `matches()` → `HasMany TournamentMatch`.
- `app/Modules/Tournaments/Models/TournamentPool.php`

### Phase BR3 — TournamentPoolCompetitor — New System Model

- `app/System/Tournament/Models/TournamentPoolCompetitor.php` — extends `AppBaseModel`. Fillable: `pool_id`, `registration_id`, `finishing_position`. Relationships: `pool()`, `registration()`.
- `app/Modules/Tournaments/Models/TournamentPoolCompetitor.php`

### Phase BR4 — Pool Sub-resource

- Routes: nested under bracket — `tournaments.categories.brackets.pools.*`
- Pool controller: `index`, `store`, `destroy`
- Pool competitor controller: `index` (list pool members), `store` (assign registration), `destroy` (remove)
- Frontend: pool management view within bracket detail (only shown when `type === PoolFinal`)

---

## Module: Tournament Matches

**Existing module — major overhaul. Depends on**: Module: Tournament Brackets & Pools.

### Phase TM1 — Tournament Match System Model Rewrite

Rewrite `app/System/Tournament/Models/TournamentMatch.php`:
- Remove: `winner_id → players.id`, `mat_number tinyint` (both dropped in migrations)
- Fillable (new): `bracket_id`, `pool_id`, `mat_id`, `bracket_section`, `round_number`, `round_name`, `match_number`, `status`, `match_outcome`, `is_golden_score`, `golden_score_duration_seconds`, `winning_competitor_id`, `winner_next_match_id`, `winner_next_position`, `loser_next_match_id`, `loser_next_position`, `scheduled_at`, `completed_at`, `notes`
- Casts: `bracket_section → BracketSectionEnum`, `status → MatchStatusEnum`, `match_outcome → MatchOutcomeEnum`, `winner_next_position → CompetitorPositionEnum`, `loser_next_position → CompetitorPositionEnum`, `is_golden_score → boolean`
- Relationships: `bracket()`, `pool()`, `mat()` → `BelongsTo VenueMat`, `competitors()` → `HasMany TournamentMatchCompetitor`, `winningCompetitor()` → `BelongsTo TournamentMatchCompetitor`, `winnerNextMatch()` → `BelongsTo TournamentMatch`, `loserNextMatch()` → `BelongsTo TournamentMatch`, `referees()` → `HasMany TournamentMatchReferee`

### Phase TM2 — TournamentMatchCompetitor System Model Update

Update `app/System/Tournament/Models/TournamentMatchCompetitor.php`:
- Casts: `position → CompetitorPositionEnum`, `result → CompetitorResultEnum`
- Add relationship: `events()` → `HasMany TournamentMatchEvent`

### Phase TM3 — Match Result Recording

Rewrite `update-result` action in the matches controller:
- Accept: `winning_competitor_id`, `match_outcome`, `is_golden_score`, `golden_score_duration_seconds`
- Validate: `winning_competitor_id` must belong to this match's competitors
- Update `TournamentMatchCompetitor.result` for winner (`Win`) and loser (`Loss`)
- Propagate winner to next match slot (`winner_next_match_id` + `winner_next_position`)

### Phase TM4 — Match Frontend Update

- `resources/js/pages/tournaments/matches/index.tsx`:
  - Replace `winner_id` player display with `winningCompetitor.registration.player.name`
  - Add `match_outcome` label column
  - Add `mat_id` (mat display_name) column
- Result recording modal: dropdown for `winning_competitor_id` (shows competitor names), `match_outcome` enum select, golden score toggle + duration input
- Update TypeScript `TournamentMatch` type: all new fields, remove old ones

Update `wiki/TournamentsModule.md`.

---

## Module: Tournament Match Events (New)

**Depends on**: Module: Tournament Matches Phase TM1, Global Phase 1 (MatchEventTypeEnum created).

### Phase ME1 — System Model

- `app/System/Tournament/Models/TournamentMatchEvent.php` — extends `AppBaseModel`. Fillable: `match_competitor_id`, `event_type`, `elapsed_seconds`, `occurred_at`, `notes`. Casts: `event_type → MatchEventTypeEnum`. Relationship: `matchCompetitor()` → `BelongsTo TournamentMatchCompetitor`.
- `app/Modules/Tournaments/Models/TournamentMatchEvent.php`

### Phase ME2 — Controller & Routes

- Routes: `POST manage/tournaments/{t}/categories/{c}/matches/{m}/events` (record event), `GET` (list event log)
- Controller actions: `index` (replay log sorted by `elapsed_seconds`), `store` (record new event)
- Soft-delete is supported but undo should use `destroy` + re-record pattern (not hard delete)

### Phase ME3 — Frontend

- Event log table on match detail: timestamp, competitor (White/Blue), event type label, elapsed time
- Score derivation: compute current `score` and `penalties` from event log (sum Ippon/WazaAri/Shido events) to display live totals

### Phase ME4 — Factory & Tests

- `database/factories/TournamentMatchEventFactory.php`
- Tests: record event, list replay, derived score matches persisted score/penalties

---

## Module: Tournament Match Referees (New)

**Depends on**: Module: Tournament Matches Phase TM1, Module: Referees.

### Phase MR1 — System Model

- `app/System/Tournament/Models/TournamentMatchReferee.php` — extends `AppBaseModel`. Fillable: `match_id`, `referee_id`, `role`. Casts: `role → RefereeRoleEnum`. Relationships: `match()`, `referee()`.
- `app/Modules/Tournaments/Models/TournamentMatchReferee.php`

### Phase MR2 — Controller & Routes

- Routes: nested under match — `tournaments.categories.matches.referees.store` / `.destroy`
- Controller: `store` (assign referee to match + role), `destroy` (remove assignment)
- Unique constraint enforced at DB level (`match_id`, `role`, `deleted_at`) — catch `UniqueConstraintViolationException` in repository and return a validation error

### Phase MR3 — Frontend

- Referee assignment panel on match detail: three role slots (Center / Side / Video), each shows assigned referee name with remove button and an assign dropdown

### Phase MR4 — Factory & Tests

- `database/factories/TournamentMatchRefereeFactory.php`
- Tests: assign, remove, duplicate role rejected

---

## Module: Tournament Weigh-ins (New)

**Depends on**: Module: Tournament Registrations Phase TR1, Module: Tournament Categories Phase TC1 (decimal weight bounds).

### Phase WI1 — System Model

- `app/System/Tournament/Models/TournamentWeighIn.php` — extends `AppBaseModel`. Fillable: `registration_id`, `weight`, `weighed_at`, `weighed_by`, `made_weight`, `notes`. Casts: `weight → 'decimal:2'`, `made_weight → boolean`. Relationships: `registration()`, `weighedBy()` → `BelongsTo User`.
- `app/Modules/Tournaments/Models/TournamentWeighIn.php`

### Phase WI2 — Controller & Routes

- Routes: nested under registration — `tournaments.categories.registrations.weigh-ins.index` / `.store`
- Controller: `index` (weigh-in history), `store` (record new weigh-in)
- Auto-compute `made_weight`: compare `weight` against the category's `min_weight` / `max_weight` at time of recording

### Phase WI3 — Frontend

- Weigh-in panel on registration detail: history table + record form (weight input, auto-shows pass/fail)

### Phase WI4 — Factory & Tests

- `database/factories/TournamentWeighInFactory.php`
- Tests: made_weight computed correctly, history ordered by `weighed_at`

---

## Module: Tournament Registration Coaches (New)

**Depends on**: Module: Tournament Registrations, Module: Coaches.

### Phase RC1 — System Model

- `app/System/Tournament/Models/TournamentRegistrationCoach.php` — extends `AppBaseModel`. Fillable: `registration_id`, `coach_id`. Relationships: `registration()`, `coach()`.
- `app/Modules/Tournaments/Models/TournamentRegistrationCoach.php`

### Phase RC2 — Controller & Routes

- Routes: `tournaments.categories.registrations.coaches.store` / `.destroy`
- Controller: `store` (assign coach), `destroy` (remove assignment)
- Unique constraint: `(registration_id, coach_id, deleted_at)` — handle duplicate gracefully

### Phase RC3 — Frontend

- Coach assignment panel on registration detail: list assigned coaches with remove button + add dropdown

---

## Module: Financial Tables (New)

**Depends on**: Module: Tournament Categories, Module: Tournament Registrations.

### Phase FIN1 — Tournament Registration Fees

- `app/System/Tournament/Models/TournamentRegistrationFee.php` — extends `AppBaseModel`. Fillable: `tournament_category_id`, `amount`, `currency`. Casts: `amount → 'decimal:2'`. Relationship: `category()`.
- `app/Modules/Tournaments/Models/TournamentRegistrationFee.php`
- Routes: `tournaments.categories.fees.store` / `.update` / `.destroy` (one fee per category, but allow updates)
- Controller: `store` (set fee), `update`, `destroy`
- Frontend: fee field inline within category edit form

### Phase FIN2 — Tournament Registration Payments

- `app/System/Tournament/Models/TournamentRegistrationPayment.php` — extends `AppBaseModel`. Fillable: `registration_id`, `amount_paid`, `currency`, `paid_at`, `method`, `reference`. Casts: `method → PaymentMethodEnum`, `amount_paid → 'decimal:2'`. Relationship: `registration()`.
- `app/Modules/Tournaments/Models/TournamentRegistrationPayment.php`
- Routes: `tournaments.categories.registrations.payments.index` / `.store`
- Controller: `index` (payment history), `store` (record payment)
- Frontend: payment panel on registration detail; show total paid vs fee due

### Phase FIN3 — Factory & Tests

- `database/factories/TournamentRegistrationFeeFactory.php`
- `database/factories/TournamentRegistrationPaymentFactory.php`
- Tests: payment recorded, total paid computed, payment method enum cast

---

## Global Phase 4 — Factories, Seeders & Test Suite

**Depends on**: All modules above.

### 4.1 Fix Existing Factories

| Factory | Fix needed |
|---------|-----------|
| `ClubFactory` | `abbreviation` via `strtoupper(fake()->bothify('???'))` to avoid empty string |
| `PlayerFactory` | `nationality_code` from seeded countries; `current_belt_rank_id` nullable from belt_ranks |
| `TournamentFactory` | Fill `name`, `level`, `status`, `start_at`, `venue_id` (via `Venue::factory()`), `rule_set_id` |
| `AddressFactory` | Rename `address` → `address_line_1`; add `country_code` from seeded countries |

### 4.2 Complete Missing Factories

All new model factories listed in each module phase above, plus:

- `TournamentCategoryFactory` — decimal `min_weight`/`max_weight` (e.g. `fake()->randomFloat(2, 40, 60)`)
- `TournamentBracketFactory` — pick random `BracketTypeEnum`
- `TournamentMatchFactory` — random `bracket_id`, round, match_number; no `winner_id`
- `TournamentMatchCompetitorFactory` — two states: `white` (position=0) / `blue` (position=1)
- `TournamentResultFactory`

### 4.3 DatabaseSeeder Wiring Order

```
CountrySeeder
BeltRankSeeder
TournamentRuleSetSeeder
RoleSeeder
DefaultAdminSeeder        ← assigns super_admin role
VenueSeeder
ClubSeeder
PlayerSeeder
TournamentSeeder          ← one full tournament with categories, registrations, bracket, matches, result
```

### 4.4 TournamentSeeder — Full Demo Scenario

`TournamentSeeder` must produce at minimum:
1. One tournament with `IJF 2024` rule set and a venue
2. Two categories (Male Cadets -66kg, Female Juniors -57kg)
3. At least 4 registrations per category (two clubs, four players each)
4. One generated bracket per category (`SingleElimination`)
5. All matches seeded with two competitors each
6. Semi-final and final results recorded with `winning_competitor_id` set
7. Two `TournamentResult` rows per category (Gold + Silver)

This serves as both demo data and a bracket-generation smoke test.

### 4.5 Minimum Feature Test Coverage

For each module, tests must cover:

- **Happy path**: create → read → update → soft-delete
- **Soft-delete + recreate**: verify soft-delete-safe unique allows re-registration
- **CHECK constraints**: submit invalid age/weight range — assert DB-level rejection
- **FK restrict**: attempt to hard-delete a parent with active children — assert RESTRICT fires
- **Match winner integrity**: `winning_competitor_id` must belong to the match
- **Role guards**: `tournament_manager` can manage tournaments; `club_admin` cannot access venues
