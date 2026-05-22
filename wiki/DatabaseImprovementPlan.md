# DojoHub — Database Improvement Plan

> **Status**: Drafted 2026-05-18. Pending implementation.
> **Scope**: Database/schema layer only. Application logic and frontend changes follow the schema changes per module.
> **Source audit**: Comparison of `database/migrations/*`, `app/System/**/Models/*`, `app/System/Tournament/Foundation/Enums/*`, and `wiki/Database.md`.
>
> Implement phases **in order** — each one assumes the previous phase is merged and migrated. Inside a phase, individual items are independent unless explicitly noted.
>
> **Conventions**
> - Every schema change ships with: migration, model/cast update, factory update, and a feature test that exercises the new column/constraint.
> - Run `vendor/bin/pint --dirty` after PHP changes, then `php artisan migrate:fresh --seed && php artisan test`.
> - When the migration ↔ wiki diverge, update both in the **same commit** and treat `wiki/Database.md` as the new source of truth.

---

## Table of Contents

1. [Phase 1 — Reconcile contradictions between migrations and wiki](#phase-1--reconcile-contradictions-between-migrations-and-wiki)
2. [Phase 2 — Soft-delete-safe unique indexes](#phase-2--soft-delete-safe-unique-indexes)
3. [Phase 3 — Core correctness in the tournament hierarchy](#phase-3--core-correctness-in-the-tournament-hierarchy)
4. [Phase 4 — CHECK constraints and missing indexes](#phase-4--check-constraints-and-missing-indexes)
5. [Phase 5 — Enum hygiene](#phase-5--enum-hygiene)
6. [Phase 6 — Factories & seeders for testability](#phase-6--factories--seeders-for-testability)
7. [Phase 7 — Localization (country code + timezones)](#phase-7--localization-country-code--timezones)
8. [Phase 8 — New domain tables (RBAC, referees, mats, live scoring, weigh-in, belt, coaches, pools, rule sets, payments, audit log, notifications)](#phase-8--new-domain-tables)
9. [Cross-cutting follow-ups](#cross-cutting-follow-ups)
10. [Open questions to confirm before implementation](#open-questions-to-confirm-before-implementation)

---

## Phase 1 — Reconcile contradictions between migrations and wiki

These are silent landmines. Pick the source of truth per item and update **both** sides.

> **Project rule — FK delete behaviour is RESTRICT everywhere.** Every domain row is soft-deleted (never hard-deleted), so `ON DELETE RESTRICT` never fires in practice and preserves referential integrity if someone ever runs an accidental hard delete. The wiki currently lists several FKs as `set null`/`cascade`; those entries are wrong — reconcile to **RESTRICT**.

| # | Field | Migration | Wiki | Recommended source of truth |
|---|------|-----------|------|-----------------------------|
| 1.1 | `clubs.address_id` | NOT NULL, `onDelete restrict` | nullable, `onDelete set null` | **Wiki** — make column nullable; FK stays `RESTRICT` (soft-delete convention). |
| 1.2 | `clubs.abbreviation` | NOT NULL `string(10)` | implied optional ("short club code") | **Migration** — keep NOT NULL; wiki should call it required and add a generation rule. |
| 1.3 | `players.nationality` | NOT NULL `string(100)` | nullable | **Wiki** — make column nullable. |
| 1.4 | `tournament_matches.winner_id` | `onDelete restrict` | `set null` | **Wiki** — keep `RESTRICT`. Players are soft-deleted, so the FK never fires; nulling out the winner would drop audit data on a hard delete. (Also see [Phase 3](#phase-3) — this column is being replaced.) |
| 1.5 | `tournament_matches.winner_next_match_id` / `loser_next_match_id` | `onDelete restrict` (self-ref) | `set null` | **Wiki** — keep `RESTRICT`. Matches are soft-deleted; cascading the structural pointer to null would corrupt bracket history on the only event that fires it (a hard delete). |
| 1.6 | `clubs_players.belt_rank` (`tinyInt`) and `grade` (`string(50)`) | Present in migration & pivot | Not documented at all | **Document or remove** — see Phase 5 ("Belt rank as enum") and Phase 8 ("Belt rank table"). For Phase 1, at minimum: add to wiki with their current semantics, and decide whether `grade` is the free-text "belt grade" or the broader "rank certificate name". |
| 1.7 | `TournamentTypeEnum` | Exists, marked `@deprecated` | Not in wiki | **Delete** the enum class and remove all references (`grep -r TournamentTypeEnum`). |

> **General reconciliation rule for Phase 1**: any wiki entry that says `set null` or `cascade` on a FK pointing at a soft-deleted parent should be corrected to `RESTRICT`. `cascade` on `tournament_brackets → tournament_categories` and `tournament_matches → tournament_brackets` is similarly suspect — review and align with the soft-delete convention while doing this pass.

### Deliverables for Phase 1
- Single migration `2026_05_XX_phase1_reconcile_schema.php` containing items 1.1, 1.3, 1.4, 1.5.
- Wiki edits in the same commit for items 1.2 and 1.6.
- Delete `app/System/Tournament/Foundation/Enums/TournamentTypeEnum.php` (item 1.7).
- Update affected requests/forms: `ClubCreateRequest`, `ClubUpdateRequest`, `PlayerCreateRequest`, `PlayerUpdateRequest` to mark nationality/address as `nullable`.
- Update `Database.md` and `ClubsModule.md` / `PlayersModule.md` to match.

---

## Phase 2 — Soft-delete-safe unique indexes

Every unique index in the schema currently ignores `deleted_at`. After a soft-delete + recreate cycle they will collide.

Affected indexes:
- `tournament_categories.tc_division_unique` on `(tournament_id, gender, age_category, weight_category)`
- `tournament_registrations` unique on `(tournament_category_id, player_id)`
- `tournament_match_competitors` unique on `(match_id, position)`
- `tournament_match_competitors` unique on `(match_id, registration_id)`
- `tournament_results` unique on `(registration_id)`
- `users.email` unique
- `addresses` (none today but if any are added)

### Strategy

MySQL 8 does not support partial indexes. Two viable patterns — choose **one** project-wide:

**Option A — Composite unique including `deleted_at`** (recommended)
```php
$table->unique(['tournament_id', 'gender', 'age_category', 'weight_category', 'deleted_at'], 'tc_division_unique');
```
Pros: works in MySQL 8, no app-level discipline needed. Cons: `deleted_at` is NULL for live rows so two live rows still collide correctly; but two soft-deleted rows with the **same** `deleted_at` value (sub-second collision) will still trip. In practice this is acceptable — pick this pattern unless someone proves the edge case happens.

**Option B — Drop unique, enforce in repository**
Pros: maximum flexibility. Cons: invariant lives in PHP only; future code can break it.

### Deliverables for Phase 2
- Migration `2026_05_XX_phase2_soft_delete_safe_uniques.php` dropping and re-creating each unique listed above using **Option A**.
- Feature tests that: (a) soft-delete a row, (b) re-create with the same key, (c) assert success.

---

## Phase 3 — Core correctness in the tournament hierarchy

The biggest semantic gap. Three changes, in this order.

### 3.1 Replace `tournament_matches.winner_id` with `winning_competitor_id`

**Why**: the wiki rule is "Player participants are not stored directly on this table" but `winner_id → players.id` violates it. A player can be flagged as winner without being a registered competitor. The match's winner must be one of the two `tournament_match_competitors` rows for that match.

**Migration**
1. Add `winning_competitor_id ulid nullable` → FK `tournament_match_competitors.id` `onDelete set null`.
2. Backfill from existing data (if any): for each match where `winner_id` is set, find the matching competitor row by `match_id + registration_id.player_id = winner_id` and copy its ID across.
3. Drop `winner_id`.

**Model updates**
- `TournamentMatch::winner()` now returns `BelongsTo TournamentMatchCompetitor`.
- Add `winnerPlayer()` accessor that traverses `winningCompetitor.registration.player` (so old call sites can read the player cheaply).

### 3.2 Unify `position` semantics across `tournament_matches` and `tournament_match_competitors`

**Today**:
- `tournament_match_competitors.position`: `0=White, 1=Blue` (via `CompetitorPositionEnum`)
- `tournament_matches.winner_next_position` / `loser_next_position`: documented as `1 or 2`

**Decision**: make `winner_next_position` and `loser_next_position` use `CompetitorPositionEnum` (0/1). The bracket builder must place winners into White or Blue slot of the next match — same enum, same meaning.

**Migration**
- Update the comment, no value change required if values were already 0/1 in code; otherwise transform `1→0, 2→1`.
- Add CHECK constraint `winner_next_position IN (0,1)` and same for `loser_next_position`.

**Model updates**
- Cast both `winner_next_position` and `loser_next_position` to `CompetitorPositionEnum`.

### 3.3 Tighten weight type consistency

`tournament_categories.min_weight/max_weight` are `int unsigned` (kg) but `tournament_registrations.player_weight` is `decimal(5,2)`. A 73.5 kg registrant has no defined home in `-73`/`-81`.

**Migration**
- Change `tournament_categories.min_weight` and `max_weight` to `decimal(5,2) unsigned`.
- No data transform needed — existing integer values cast cleanly.

### Deliverables for Phase 3
- Migration `2026_05_XX_phase3_match_winner_competitor.php`
- Migration `2026_05_XX_phase3_unify_position_enum.php`
- Migration `2026_05_XX_phase3_decimal_weight_bounds.php`
- Model + cast updates for `TournamentMatch`, `TournamentCategory`.
- Tests:
  - Cannot set winner to a competitor from a different match.
  - Position enum applied symmetrically.
  - Registration weight `73.5` fits exactly one category.

---

## Phase 4 — CHECK constraints and missing indexes

### 4.1 CHECK constraints

| Table | Constraint |
|-------|------------|
| `tournament_categories` | `min_age <= max_age` |
| `tournament_categories` | `min_weight <= max_weight` |
| `tournaments` | `end_at IS NULL OR start_at <= end_at` |
| `clubs_players` | `ended_at IS NULL OR started_at <= ended_at` |
| `tournament_match_competitors` | `score IS NULL OR score BETWEEN 0 AND 2` |
| `tournament_match_competitors` | `penalties IS NULL OR penalties BETWEEN 0 AND 3` |

CHECK constraints in MySQL 8 are real — use `$table->rawIndex` or raw `DB::statement` if Blueprint doesn't expose them in this Laravel version. Confirm with `search-docs` before writing.

### 4.2 Missing indexes (filtered/sorted often)

- `tournament_registrations(player_id)`
- `tournament_registrations(club_id)`
- `tournament_registrations(status)`
- `tournament_matches(winner_id)` *(replaced by `winning_competitor_id` after Phase 3 — index that instead)*
- `tournament_matches(status)`
- `tournament_matches(scheduled_at)`
- `tournaments(venue_id)`
- `tournaments(start_at)`
- `tournaments(status)`
- `tournament_categories(status)`

### 4.3 Editor-trait FK behaviour — leave as RESTRICT

`Database/Traits/EditorTrait.php` registers `created_by`, `updated_by`, `deleted_by` FKs with **no** `onDelete` clause → defaults to RESTRICT.

**Decision**: keep RESTRICT. Users are always soft-deleted, so RESTRICT never fires in practice and preserves audit-trail integrity if anyone runs an accidental hard delete. Do **not** switch to `set null`.

**Action for this phase**: only **document** the convention in `wiki/Architecture.md` (or alongside the EditorTrait comment) so future contributors don't change it. No migration needed.

### Deliverables for Phase 4
- Migration `2026_05_XX_phase4_check_constraints.php`
- Migration `2026_05_XX_phase4_indexes.php`
- Wiki note on the RESTRICT-everywhere convention (no migration for 4.3).

---

## Phase 5 — Enum hygiene

### 5.1 `PlayerGenderEnum` case casing
Cases are `MALE` / `FEMALE` (UPPER_CASE). Project & Laravel Boost convention is TitleCase. Rename to `Male` / `Female`.

**Migration**: pure code change — integer values don't change, so no data migration. Update every `PlayerGenderEnum::MALE` reference (controllers, repositories, factories, tests, frontend type if mirrored).

### 5.2 Delete `TournamentTypeEnum`
Already covered by Phase 1.7 — keep here as a checklist item.

### 5.3 Rename `BracketSectionEnum::Final`
Overlaps conceptually with `BracketTypeEnum::PoolFinal`. Rename to `BracketSectionEnum::GoldFinal` (or `Championship`) to disambiguate.

### 5.4 Optional — extract a `LifecycleStatusEnum` base
`MatchStatusEnum`, `BracketStatusEnum`, `CategoryStatusEnum`, `TournamentStatusEnum` all share `Pending/Active/Completed`. Consider a shared trait or interface so adding a status (e.g. `Cancelled`) to one prompts re-evaluation of the others.

This is a code-organisation change; do it only if it stops being a stylistic cleanup and starts paying for itself.

### Deliverables for Phase 5
- Renames + grep-and-fix across the codebase.
- Update `wiki/Enums.md`.

---

## Phase 6 — Factories & seeders for testability

### 6.1 Complete missing factories

| Factory | Status | Action |
|---------|--------|--------|
| `TournamentFactory` | `definition()` returns `[]` | Fill in: name, level, start_at, end_at, status, venue (via `Venue::factory()`). |
| `TournamentCategoryFactory` | missing | Create. |
| `TournamentRegistrationFactory` | missing | Create — link a player + club + category. |
| `TournamentBracketFactory` | missing | Create. |
| `TournamentMatchFactory` | missing | Create — random bracket, round, match_number. |
| `TournamentMatchCompetitorFactory` | missing | Create — two states for `White` / `Blue`. |
| `TournamentResultFactory` | missing | Create. |

### 6.2 Fix existing factories

- `ClubFactory::definition()` — `abbreviation` can become `""` after `preg_replace`. Replace with `strtoupper($this->faker->bothify('???'))` or a deterministic generator.
- `ClubFactory` — set `logo_url` via `optional()`.
- `PlayerFactory` — set `nationality` to nullable (after Phase 1.3) and `profile_photo_url` via `optional()`.

### 6.3 Seeders

- Flesh out `TournamentSeeder` so that `php artisan migrate:fresh --seed` produces at least one full tournament with categories, registrations, a generated bracket, and a recorded result. This becomes the demo data and a smoke test for the bracket builder.
- Add `BeltRankSeeder` once Phase 8 lands the belt-rank table.

### Deliverables for Phase 6
- New factories listed above.
- Updated existing factories.
- `TournamentSeeder` populated.

---

## Phase 7 — Localization (country code + timezones)

### 7.1 ISO country codes
- Add `country_code char(2)` to `addresses` and `players` (nullable for now, backfilled when known).
- Long-term: deprecate `addresses.country` (string) and `players.nationality` (string) once UI is migrated to country code dropdown.
- Add `postal_code string(20) nullable` and `address_line_2 string nullable` to `addresses`.
- Optional: `latitude decimal(10,7) nullable`, `longitude decimal(10,7) nullable` on `addresses` for venue mapping.

### 7.2 Tournament timezone
- Add `timezone string(64) nullable` to `tournaments` (IANA name e.g. `Asia/Kathmandu`). When set, all `scheduled_at`/`completed_at` within that tournament's matches should be rendered in that timezone.
- (No DB change needed for the dateTime columns themselves — keep as UTC-ish dateTime.)

### Deliverables for Phase 7
- Migration `2026_05_XX_phase7_localization.php`
- UI follow-up tickets (out of scope for the schema plan).

---

## Phase 8 — New domain tables

These are independent slices. Implement only the slices the next user-visible feature needs. Each subsection lists the **purpose**, the **table sketch**, and the **blocking dependencies**.

### 8.1 RBAC — roles & permissions
**Purpose**: `users` is currently a flat table. We need Tournament Manager, Club Admin, Referee, Player roles.
**Recommendation**: use [spatie/laravel-permission] (already-conventional, well-supported) rather than rolling our own. Adds `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.
**Blocking dependencies**: none.

### 8.2 Referees + referee assignments per match
**Purpose**: today the referee context lives in `tournament_matches.notes`. Need first-class referees.

```
referees
  id ulid PK
  user_id  → users.id (nullable — a referee may not have a login account)
  full_name string
  nationality char(2) nullable
  certification_level tinyint nullable (enum: National/Continental/International)
  audit
```
```
tournament_match_referees
  id ulid PK
  match_id → tournament_matches.id (cascade)
  referee_id → referees.id (restrict)
  role tinyint  (0=Center, 1=Side, 2=Video)
  audit
  UNIQUE (match_id, role)  -- one referee per role per match
```
**Blocking dependencies**: none (can land before RBAC).

### 8.3 Mats / tatamis
**Purpose**: `tournament_matches.mat_number` is a free integer with no FK; you can schedule a match on mat 7 at a 3-mat venue.

```
venue_mats
  id ulid PK
  venue_id → venues.id (cascade)
  mat_number tinyint
  display_name string nullable    -- "Tatami A"
  audit
  UNIQUE (venue_id, mat_number)
```
**Migration follow-up**:
- Replace `tournament_matches.mat_number` (tinyint) with `mat_id ulid nullable FK venue_mats.id`.
- Drop `venues.mat_count` (now derived).

**Blocking dependencies**: none. But touches `tournament_matches` so ideally land before/with the live-scoring slice (8.4).

### 8.4 Match events (live scoring)
**Purpose**: today only the final score and penalty count are stored. We cannot replay a match, attribute actions, or distinguish Ippon from Waza-ari × 2.

```
tournament_match_events
  id ulid PK
  match_competitor_id → tournament_match_competitors.id (cascade)
  event_type tinyint   -- enum: Ippon, Waza-ari, Shido, Hansoku-make, Hansoku-make-light, Kinsa, Note
  elapsed_seconds int unsigned   -- time on the match clock when event occurred
  occurred_at datetime           -- wall-clock timestamp
  notes string nullable
  audit
  INDEX (match_competitor_id, elapsed_seconds)
```

Add `match_outcome tinyint` enum to `tournament_matches`: `Ippon / Waza-ari / Decision / Hansoku-make / Walkover / Bye / GoldenScore`. Replaces today's reliance on the freeform `notes` column for outcome type.

Add `is_golden_score boolean default false` and `golden_score_duration int nullable` to `tournament_matches`.

**Blocking dependencies**: Phase 3 (winning_competitor_id).

### 8.5 Weigh-in records
**Purpose**: `tournament_registrations.player_weight` is a snapshot with no provenance.

```
tournament_weigh_ins
  id ulid PK
  registration_id → tournament_registrations.id (restrict)
  weight decimal(5,2) unsigned
  weighed_at datetime
  weighed_by → users.id (set null)
  made_weight boolean    -- denormalised: weight within category bounds at weigh-in time
  notes string nullable
  audit
  INDEX (registration_id, weighed_at)
```
**Blocking dependencies**: Phase 3.3 (decimal bounds) so comparisons line up.

### 8.6 Belt rank — first-class
**Purpose**: belt rank is fundamental in Judo. Currently a free tinyint on `clubs_players` with no enum and a sibling free-text `grade`.

Two questions to resolve first:
1. Is belt rank a property of the **player** (career rank) or the **club membership** (rank certified by that club)?
2. Do we model belts as a fixed enum (Kyu 6 → Dan 10) or as a `belt_ranks` table?

**Recommended**:
- A `belt_ranks` reference table seeded with the standard 16 belts (Rokkyu through Judan), `id ulid PK, code string(10) unique, name string, kind enum(Kyu/Dan), order tinyint`.
- Add `players.current_belt_rank_id` (nullable, FK).
- Keep `clubs_players.belt_rank_id` (FK) as the rank **at time of joining** and `clubs_players.grade` as the awarded certificate name. Document both in `wiki/ClubPlayers.md`.

**Blocking dependencies**: Phase 1.6 (decide on `belt_rank` / `grade` semantics first).

### 8.7 Coaches + coach assignments
```
coaches
  id ulid PK
  user_id → users.id (nullable)
  full_name string
  audit

tournament_registration_coaches
  id ulid PK
  registration_id → tournament_registrations.id (cascade)
  coach_id → coaches.id (restrict)
  audit
  UNIQUE (registration_id, coach_id)
```
**Blocking dependencies**: none.

### 8.8 Pools (Pool + Final bracket type)
**Purpose**: `BracketTypeEnum::PoolFinal` exists but no schema supports grouping competitors into round-robin pools.

```
tournament_pools
  id ulid PK
  bracket_id → tournament_brackets.id (cascade)
  name string(50)         -- "Pool A"
  audit

tournament_pool_competitors
  id ulid PK
  pool_id → tournament_pools.id (cascade)
  registration_id → tournament_registrations.id (restrict)
  finishing_position tinyint nullable
  audit
  UNIQUE (pool_id, registration_id)
```
Add `pool_id` (nullable FK) to `tournament_matches` so a pool's round-robin matches link to their pool.

**Blocking dependencies**: none, but coordinate with the BracketService rewrite.

### 8.9 Scoring rule set per tournament
**Purpose**: Waza-ari ≤ 2 and Shido ≤ 3 are hardcoded by comment. Different federations have different rules.

```
tournament_rule_sets
  id ulid PK
  name string                       -- "IJF 2024", "Local Modified"
  max_waza_ari tinyint default 2
  max_shido tinyint default 3
  match_duration_seconds int default 240
  golden_score_seconds int nullable
  audit
```
Add `tournament_rule_set_id` (nullable) to `tournaments`.

The CHECK constraints from Phase 4.1 (`score BETWEEN 0 AND 2`) become wrong once rule sets vary. Replace those CHECKs with application-level validation that reads the tournament's rule set.

**Blocking dependencies**: Phase 4 (review CHECK constraints).

### 8.10 Payments / registration fees
```
tournament_registration_fees
  id ulid PK
  tournament_category_id → tournament_categories.id (cascade)
  amount decimal(10,2) unsigned
  currency char(3)   -- ISO 4217
  audit

tournament_registration_payments
  id ulid PK
  registration_id → tournament_registrations.id (cascade)
  amount_paid decimal(10,2)
  paid_at datetime nullable
  method tinyint  -- enum: Cash, Bank, Online
  reference string nullable
  audit
```
**Blocking dependencies**: none. Punt until product requires it.

### 8.11 Audit log
**Purpose**: `created_by/updated_by/deleted_by` capture *who last touched* a row, not *what changed*. The wiki references an "EditorLogs audit trail" but the implementation is just FK columns.

**Recommendation**: install [owen-it/laravel-auditing] and opt-in via a trait per model. Stores a JSON diff of old/new values per change.

**Blocking dependencies**: none.

### 8.12 Notifications
Stub the standard Laravel `notifications` table (`php artisan notifications:table`) once the first email/in-app notification need lands.

---

## Cross-cutting follow-ups

- Update `Database.md` after each phase. Use a "Last reconciled: YYYY-MM-DD" line per table.
- After every phase, re-run `php artisan migrate:fresh --seed && php artisan test` and `npm run types`.
- When schema columns leak into the TypeScript types (`resources/js/types/*`), update both in the same PR.
- The address lifecycle ("address records are created/deleted alongside their owning entity") is only enforced in PHP today. Decide whether to:
  - leave it that way and document in `wiki/VenuesModule.md` / `wiki/ClubsModule.md`, or
  - move to a polymorphic `addressable_id, addressable_type` on `addresses` and cascade delete.

---

## Open questions to confirm before implementation

1. **Phase 1.1** — confirm we want `clubs.address_id` nullable (matches the wiki). Any active code that assumes it's always present?
2. **Phase 1.6** — what does `clubs_players.grade` mean today? Belt grade (string version of `belt_rank`) or the certificate/award name?
3. **Phase 2** — Option A (composite unique with `deleted_at`) vs Option B (drop unique + app enforcement)?
4. **Phase 3.2** — is the value space of `winner_next_position` already 0/1 in code, or really 1/2? Determines whether we need a data transform.
5. **Phase 8.1** — pull in `spatie/laravel-permission`, or roll our own minimal RBAC?
6. **Phase 8.6** — fixed belt enum or `belt_ranks` reference table?
7. **Phase 8.11** — `owen-it/laravel-auditing`, a custom `audit_logs` table, or stick with `created_by/updated_by/deleted_by`?

Answer these (or pin to "decide later") at the start of each phase so the implementation doesn't stall mid-PR.
