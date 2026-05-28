# Database Migrations

This document is the authoritative schema reference for all 34 migration files in `database/migrations/`. Migrations run in filename order. All domain tables use ULID primary keys and the `EditorTrait` macro (see [EditorTrait.md](EditorTrait.md)) unless noted.

**FK behavior on domain tables:** `onDelete('restrict')` / `onUpdate('cascade')` everywhere, which is safe because soft-delete fires an `UPDATE` rather than a `DELETE`.

**CHECK constraints** are wrapped in `if (DB::getDriverName() !== 'sqlite')` guards so they run on MySQL/PostgreSQL but are skipped during SQLite-backed testing.

---

## Table of Contents

1. [Framework / Scaffold Tables](#1-framework--scaffold-tables)
2. [Lookup Tables](#2-lookup-tables)
3. [Venue Tables](#3-venue-tables)
4. [Person Tables](#4-person-tables)
5. [Club Tables](#5-club-tables)
6. [Tournament Tables](#6-tournament-tables)
7. [Authorization Tables (Spatie)](#7-authorization-tables-spatie)

---

## 1. Framework / Scaffold Tables

### `users` — `0001_01_01_000000`

Authentication accounts. Primary key is a standard auto-increment bigint (not a ULID). Audit columns are defined manually with `onDelete('set null')` / `onUpdate('cascade')` to handle the self-referential FK safely.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `bigint` unsigned auto-increment | no | PK |
| `name` | `varchar(255)` | no | |
| `email` | `varchar(255)` | no | unique |
| `email_verified_at` | `timestamp` | yes | |
| `password` | `varchar(255)` | no | |
| `remember_token` | `varchar(100)` | yes | |
| `created_by` | `unsignedBigInteger` | yes | FK users.id SET NULL |
| `updated_by` | `unsignedBigInteger` | yes | FK users.id SET NULL |
| `deleted_by` | `unsignedBigInteger` | yes | FK users.id SET NULL |
| `deleted_at` | `timestamp` | yes | soft deletes |
| `created_at` | `timestamp` | yes | |
| `updated_at` | `timestamp` | yes | |

**Same migration also creates:**

**`password_reset_tokens`**

| Column | Type | Nullable |
|---|---|---|
| `email` | `varchar` | no — PK |
| `token` | `varchar` | no |
| `created_at` | `timestamp` | yes |

**`sessions`**

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `varchar` | no — PK | |
| `user_id` | `bigint` unsigned | yes | indexed |
| `ip_address` | `varchar(45)` | yes | |
| `user_agent` | `text` | yes | |
| `payload` | `longtext` | no | |
| `last_activity` | `integer` | no | indexed |

---

### `cache`, `cache_locks` — `0001_01_01_000001`

Laravel's built-in cache driver tables.

**`cache`**

| Column | Type | Nullable |
|---|---|---|
| `key` | `varchar` | no — PK |
| `value` | `mediumtext` | no |
| `expiration` | `integer` | no |

**`cache_locks`**

| Column | Type | Nullable |
|---|---|---|
| `key` | `varchar` | no — PK |
| `owner` | `varchar` | no |
| `expiration` | `integer` | no |

---

### `jobs`, `job_batches`, `failed_jobs` — `0001_01_01_000002`

Laravel's built-in queue driver tables.

**`jobs`**

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `bigint` unsigned auto-increment | no | PK |
| `queue` | `varchar` | no | indexed |
| `payload` | `longtext` | no | |
| `attempts` | `tinyint` unsigned | no | |
| `reserved_at` | `unsigned integer` | yes | |
| `available_at` | `unsigned integer` | no | |
| `created_at` | `unsigned integer` | no | Unix timestamp |

**`job_batches`**

| Column | Type | Nullable |
|---|---|---|
| `id` | `varchar` | no — PK |
| `name` | `varchar` | no |
| `total_jobs` | `integer` | no |
| `pending_jobs` | `integer` | no |
| `failed_jobs` | `integer` | no |
| `failed_job_ids` | `longtext` | no |
| `options` | `mediumtext` | yes |
| `cancelled_at` | `integer` | yes |
| `created_at` | `integer` | no |
| `finished_at` | `integer` | yes |

**`failed_jobs`**

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `bigint` unsigned auto-increment | no | PK |
| `uuid` | `varchar` | no | unique |
| `connection` | `text` | no | |
| `queue` | `text` | no | |
| `payload` | `longtext` | no | |
| `exception` | `longtext` | no | |
| `failed_at` | `timestamp` | no | default: `CURRENT_TIMESTAMP` |

---

### `audits` — `0001_01_01_000003`

owen-it/laravel-auditing package table. Table name and connection are driven by `config/audit.php`.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `bigint` unsigned auto-increment | no | PK |
| `user_type` | `varchar` | yes | morph type |
| `user_id` | `unsignedBigInteger` | yes | morph id; indexed with `user_type` |
| `event` | `varchar` | no | e.g. `created`, `updated` |
| `auditable_type` | `varchar` | no | |
| `auditable_id` | `ulid` | no | |
| `old_values` | `text` | yes | JSON |
| `new_values` | `text` | yes | JSON |
| `url` | `text` | yes | |
| `ip_address` | `ipAddress` | yes | |
| `user_agent` | `varchar(1023)` | yes | |
| `tags` | `varchar` | yes | |
| `created_at` | `timestamp` | yes | |
| `updated_at` | `timestamp` | yes | |

**Indexes:** `(user_id, user_type)`

---

## 2. Lookup Tables

### `countries` — `2026_01_01_000001`

ISO 3166-1 alpha-2 country reference. No audit columns, no soft-delete.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `code` | `char(2)` | no | PK |
| `name` | `varchar` | no | |

---

### `belt_ranks` — `2026_01_01_000002`

Judo/martial-arts grade reference. ULID PK, but no `EditorTrait` (no timestamps, no soft-delete, no audit columns). Treated as an immutable lookup table.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `code` | `varchar(10)` | no | unique; e.g. `6K`, `1K`, `1D`, `10D` |
| `name` | `varchar` | no | e.g. `Rokkyu`, `Shodan` |
| `kind` | `tinyint` unsigned | no | 0=Kyu, 1=Dan |
| `color` | `varchar(50)` | yes | belt color |
| `sort_order` | `tinyint` unsigned | no | ascending display order |

**Unique:** `code`

---

### `tournament_rule_sets` — `2026_01_01_000003`

Named rulesets governing match duration, scoring limits, and golden-score behavior. Uses `EditorTrait`.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `name` | `varchar` | no | e.g. `IJF 2024`, `Local Modified` |
| `max_waza_ari` | `tinyint` unsigned | no | default 2 |
| `max_shido` | `tinyint` unsigned | no | default 3 |
| `match_duration_seconds` | `unsigned integer` | no | default 240 (4 min) |
| `golden_score_seconds` | `unsigned integer` | yes | null = unlimited golden score |
| *(EditorTrait columns)* | | | |

---

## 3. Venue Tables

### `addresses` — `2026_01_01_000004`

Reusable postal address records referenced by venues, clubs, and players.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `address_line_1` | `varchar` | no | |
| `address_line_2` | `varchar` | yes | |
| `city` | `varchar(100)` | no | |
| `state` | `varchar(100)` | yes | |
| `postal_code` | `varchar(20)` | yes | |
| `country_code` | `char(2)` | no | FK countries.code RESTRICT |
| `latitude` | `decimal(10,7)` | yes | |
| `longitude` | `decimal(10,7)` | yes | |
| *(EditorTrait columns)* | | | |

**FKs:** `country_code` → `countries.code` (RESTRICT / CASCADE)

---

### `venues` — `2026_01_01_000005`

Physical venues where tournaments are held.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `name` | `varchar` | no | |
| `address_id` | `ulid` | no | FK addresses.id RESTRICT |
| *(EditorTrait columns)* | | | |

**FKs:** `address_id` → `addresses.id` (RESTRICT / CASCADE)

---

### `venue_mats` — `2026_01_01_000006`

Individual tatami mats within a venue. A match is assigned to a mat.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `venue_id` | `ulid` | no | FK venues.id RESTRICT |
| `mat_number` | `tinyint` unsigned | no | numeric identifier within venue |
| `display_name` | `varchar` | yes | e.g. `Tatami A`, `Mat 1` |
| *(EditorTrait columns)* | | | |

**FKs:** `venue_id` → `venues.id` (RESTRICT / CASCADE)

**Unique:** `(venue_id, mat_number, deleted_at)` — name `vm_venue_mat_unique`; includes `deleted_at` so soft-deleted records don't block reuse of a mat number.

---

## 4. Person Tables

### `players` — `2026_01_01_000008`

Athletes who compete in tournaments.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `name` | `varchar` | no | |
| `gender` | `tinyint` unsigned | no | 0=Male, 1=Female |
| `dob` | `date` | no | date of birth |
| `weight` | `decimal(5,2)` | no | kg |
| `nationality_code` | `char(2)` | yes | FK countries.code |
| `current_belt_rank_id` | `ulid` | yes | FK belt_ranks.id |
| `address_id` | `ulid` | yes | FK addresses.id |
| `email` | `varchar` | yes | |
| `profile_photo_url` | `varchar` | yes | |
| `phone_number` | `varchar(20)` | yes | |
| *(EditorTrait columns)* | | | |

**FKs:**
- `nationality_code` → `countries.code` (RESTRICT / CASCADE)
- `current_belt_rank_id` → `belt_ranks.id` (RESTRICT / CASCADE)
- `address_id` → `addresses.id` (RESTRICT / CASCADE)

---

### `referees` — `2026_01_01_000010`

Match officials. Optionally linked to a system user account.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `user_id` | `unsignedBigInteger` | yes | FK users.id RESTRICT; null if not a system user |
| `full_name` | `varchar` | no | |
| `nationality_code` | `char(2)` | yes | FK countries.code |
| `certification_level` | `tinyint` unsigned | yes | 0=Club, 1=National, 2=Continental, 3=International |
| *(EditorTrait columns)* | | | |

**FKs:**
- `user_id` → `users.id` (RESTRICT / CASCADE)
- `nationality_code` → `countries.code` (RESTRICT / CASCADE)

---

### `coaches` — `2026_01_01_000011`

Club coaches who accompany players to tournaments. Optionally linked to a system user.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `user_id` | `unsignedBigInteger` | yes | FK users.id RESTRICT |
| `full_name` | `varchar` | no | |
| `email` | `varchar` | yes | |
| `phone_number` | `varchar(20)` | yes | |
| *(EditorTrait columns)* | | | |

**FKs:** `user_id` → `users.id` (RESTRICT / CASCADE)

---

## 5. Club Tables

### `clubs` — `2026_01_01_000007`

Martial arts clubs that register players and coaches.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `name` | `varchar` | no | |
| `abbreviation` | `varchar(10)` | no | short code, e.g. `KJC` |
| `logo_url` | `varchar` | yes | |
| `address_id` | `ulid` | yes | FK addresses.id (optional) |
| `phone_number` | `varchar(20)` | yes | |
| *(EditorTrait columns)* | | | |

**FKs:** `address_id` → `addresses.id` (RESTRICT / CASCADE)

---

### `clubs_players` — `2026_01_01_000009`

Membership history of a player at a club. A player may belong to multiple clubs over time; the active membership has `ended_at = null`.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `club_id` | `ulid` | no | FK clubs.id RESTRICT |
| `player_id` | `ulid` | no | FK players.id RESTRICT |
| `belt_rank_id` | `ulid` | yes | rank at time of joining; FK belt_ranks.id |
| `grade_certificate` | `varchar(100)` | yes | certificate reference |
| `started_at` | `datetime` | no | membership start |
| `ended_at` | `datetime` | yes | null = currently active |
| *(EditorTrait columns)* | | | |

**FKs:**
- `club_id` → `clubs.id` (RESTRICT / CASCADE)
- `player_id` → `players.id` (RESTRICT / CASCADE)
- `belt_rank_id` → `belt_ranks.id` (RESTRICT / CASCADE)

**Indexes:** `(club_id, player_id)`, `player_id`, `club_id`

**CHECK:** `chk_cp_dates` — `ended_at IS NULL OR started_at <= ended_at` (MySQL/PG only)

---

### `clubs_coaches` — `2026_01_01_000028`

Employment/affiliation history of a coach at a club.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `club_id` | `ulid` | no | FK clubs.id RESTRICT |
| `coach_id` | `ulid` | no | FK coaches.id RESTRICT |
| `started_at` | `datetime` | no | |
| `ended_at` | `datetime` | yes | null = currently active |
| *(EditorTrait columns)* | | | |

**FKs:**
- `club_id` → `clubs.id` (RESTRICT / CASCADE)
- `coach_id` → `coaches.id` (RESTRICT / CASCADE)

**Indexes:** `(club_id, coach_id)`, `coach_id`, `club_id`

**CHECK:** `chk_cc_dates` — `ended_at IS NULL OR started_at <= ended_at` (MySQL/PG only)

---

## 6. Tournament Tables

### `tournaments` — `2026_01_01_000012`

The top-level tournament event.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `name` | `varchar` | no | |
| `description` | `text` | yes | |
| `logo_url` | `varchar` | yes | |
| `level` | `tinyint` unsigned | no | 1=Local, 2=Regional, 3=National, 4=International |
| `status` | `tinyint` unsigned | no | 0=Scheduled, 1=InProgress, 2=Completed, 3=Cancelled; default 0 |
| `venue_id` | `ulid` | no | FK venues.id RESTRICT |
| `rule_set_id` | `ulid` | yes | FK tournament_rule_sets.id RESTRICT |
| `timezone` | `varchar(64)` | yes | IANA timezone, e.g. `Asia/Kathmandu` |
| `start_at` | `datetime` | no | |
| `end_at` | `datetime` | yes | |
| *(EditorTrait columns)* | | | |

**FKs:**
- `venue_id` → `venues.id` (RESTRICT / CASCADE)
- `rule_set_id` → `tournament_rule_sets.id` (RESTRICT / CASCADE)

**Indexes:** `venue_id`, `status`, `start_at`

**CHECK:** `chk_tournament_dates` — `end_at IS NULL OR start_at <= end_at` (MySQL/PG only)

---

### `tournament_categories` — `2026_01_01_000013`

A division within a tournament defined by gender, age group, and weight class. Auto-generates its display name from enum labels when `name` is null.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `tournament_id` | `ulid` | no | FK tournaments.id RESTRICT |
| `name` | `varchar` | yes | auto-generated from enum labels if null |
| `gender` | `tinyint` unsigned | no | 0=Male, 1=Female |
| `age_category` | `tinyint` unsigned | no | enum value |
| `min_age` | `tinyint` unsigned | no | |
| `max_age` | `tinyint` unsigned | no | |
| `weight_category` | `tinyint` unsigned | no | enum value |
| `min_weight` | `decimal(5,2)` | no | kg |
| `max_weight` | `decimal(5,2)` | no | kg |
| `status` | `tinyint` unsigned | no | 0=Pending, 1=Seeding, 2=Active, 3=Completed; default 0 |
| `registration_open_at` | `datetime` | yes | |
| `registration_close_at` | `datetime` | yes | |
| `start_at` | `datetime` | yes | |
| `end_at` | `datetime` | yes | |
| `weigh_in_start_at` | `datetime` | yes | |
| `weigh_in_end_at` | `datetime` | yes | |
| *(EditorTrait columns)* | | | |

**FKs:** `tournament_id` → `tournaments.id` (RESTRICT / CASCADE)

**Indexes:** `tournament_id`, `status`, `registration_close_at`, `start_at`, `weigh_in_start_at`

**Unique:** `(tournament_id, gender, age_category, weight_category, deleted_at)` — name `tc_division_unique`

**CHECKs** (MySQL/PG only):
- `chk_tc_age` — `min_age <= max_age`
- `chk_tc_weight` — `min_weight <= max_weight`
- `chk_tc_registration_window` — `registration_open_at IS NULL OR registration_close_at IS NULL OR registration_open_at <= registration_close_at`
- `chk_tc_schedule` — `start_at IS NULL OR end_at IS NULL OR start_at <= end_at`
- `chk_tc_weigh_in_window` — `weigh_in_start_at IS NULL OR weigh_in_end_at IS NULL OR weigh_in_start_at <= weigh_in_end_at`

---

### `tournament_registrations` — `2026_01_01_000014`

A player's entry into a tournament category, submitted by a club.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `tournament_category_id` | `ulid` | no | FK tournament_categories.id RESTRICT |
| `club_id` | `ulid` | no | FK clubs.id RESTRICT |
| `player_id` | `ulid` | no | FK players.id RESTRICT |
| `player_age` | `tinyint` unsigned | no | age at time of registration |
| `player_weight` | `decimal(5,2)` | no | kg at time of registration |
| `status` | `tinyint` unsigned | no | 0=Registered, 1=Confirmed, 2=Withdrawn; default 0 |
| *(EditorTrait columns)* | | | |

**FKs:**
- `tournament_category_id` → `tournament_categories.id` (RESTRICT / CASCADE)
- `club_id` → `clubs.id` (RESTRICT / CASCADE)
- `player_id` → `players.id` (RESTRICT / CASCADE)

**Indexes:** `player_id`, `club_id`, `status`

**Unique:** `(tournament_category_id, player_id, deleted_at)` — name `tr_category_player_unique`

---

### `tournament_registration_fees` — `2026_01_01_000015`

Fee schedule for a tournament category.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `tournament_category_id` | `ulid` | no | FK tournament_categories.id RESTRICT |
| `amount` | `decimal(10,2)` unsigned | no | |
| `currency` | `char(3)` | no | ISO 4217, e.g. `NPR`, `USD` |
| *(EditorTrait columns)* | | | |

**FKs:** `tournament_category_id` → `tournament_categories.id` (RESTRICT / CASCADE)

---

### `tournament_registration_payments` — `2026_01_01_000016`

Payment records against a registration.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `registration_id` | `ulid` | no | FK tournament_registrations.id RESTRICT |
| `amount_paid` | `decimal(10,2)` | no | |
| `currency` | `char(3)` | no | ISO 4217 |
| `paid_at` | `datetime` | yes | null if pending |
| `method` | `tinyint` unsigned | no | 0=Cash, 1=Bank, 2=Online |
| `reference` | `varchar` | yes | transaction reference |
| *(EditorTrait columns)* | | | |

**FKs:** `registration_id` → `tournament_registrations.id` (RESTRICT / CASCADE)

**Indexes:** `registration_id`

---

### `tournament_weigh_ins` — `2026_01_01_000017`

Official weigh-in records for a registration.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `registration_id` | `ulid` | no | FK tournament_registrations.id RESTRICT |
| `weight` | `decimal(5,2)` | no | recorded weight kg |
| `weighed_at` | `datetime` | no | |
| `weighed_by` | `unsignedBigInteger` | yes | FK users.id — official who recorded it |
| `made_weight` | `boolean` | no | true if within category limit |
| `notes` | `varchar` | yes | |
| *(EditorTrait columns)* | | | |

**FKs:**
- `registration_id` → `tournament_registrations.id` (RESTRICT / CASCADE)
- `weighed_by` → `users.id` (RESTRICT / CASCADE)

**Indexes:** `(registration_id, weighed_at)`

---

### `tournament_registration_coaches` — `2026_01_01_000018`

Coaches assigned to accompany a specific registration.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `registration_id` | `ulid` | no | FK tournament_registrations.id RESTRICT |
| `coach_id` | `ulid` | no | FK coaches.id RESTRICT |
| *(EditorTrait columns)* | | | |

**FKs:**
- `registration_id` → `tournament_registrations.id` (RESTRICT / CASCADE)
- `coach_id` → `coaches.id` (RESTRICT / CASCADE)

**Unique:** `(registration_id, coach_id, deleted_at)` — name `trc_registration_coach_unique`

---

### `tournament_brackets` — `2026_01_01_000019`

The bracket (draw) for a tournament category.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `tournament_category_id` | `ulid` | no | FK tournament_categories.id RESTRICT |
| `type` | `tinyint` unsigned | no | 0=SingleElimination, 1=DoubleElimination, 2=PoolFinal, 3=RepechageIJF |
| `seeding_method` | `tinyint` unsigned | no | 0=Random, 1=Manual, 2=ByRank; default 0 |
| `competitor_count` | `tinyint` unsigned | yes | total competitors drawn |
| `round_count` | `tinyint` unsigned | yes | number of rounds |
| `status` | `tinyint` unsigned | no | 0=Pending, 1=Active, 2=Completed; default 0 |
| `generated_at` | `datetime` | yes | null until draw is generated |
| *(EditorTrait columns)* | | | |

**FKs:** `tournament_category_id` → `tournament_categories.id` (RESTRICT / CASCADE)

**Indexes:** `tournament_category_id`

---

### `tournament_pools` — `2026_01_01_000020`

Round-robin pools within a PoolFinal or RepechageIJF bracket.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `bracket_id` | `ulid` | no | FK tournament_brackets.id RESTRICT |
| `name` | `varchar(50)` | no | e.g. `Pool A`, `Pool B` |
| *(EditorTrait columns)* | | | |

**FKs:** `bracket_id` → `tournament_brackets.id` (RESTRICT / CASCADE)

---

### `tournament_pool_competitors` — `2026_01_01_000021`

Assignment of a registration to a pool, with final pool standing.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `pool_id` | `ulid` | no | FK tournament_pools.id RESTRICT |
| `registration_id` | `ulid` | no | FK tournament_registrations.id RESTRICT |
| `finishing_position` | `tinyint` unsigned | yes | null until pool play complete |
| *(EditorTrait columns)* | | | |

**FKs:**
- `pool_id` → `tournament_pools.id` (RESTRICT / CASCADE)
- `registration_id` → `tournament_registrations.id` (RESTRICT / CASCADE)

**Unique:** `(pool_id, registration_id, deleted_at)` — name `tpc_pool_registration_unique`

---

### `tournament_matches` — `2026_01_01_000022` + `2026_01_01_000027`

Individual bouts. Created in two passes: the initial migration creates all columns except the three self-referential / cross-table FKs which are added in migration `000027`.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `bracket_id` | `ulid` | no | FK tournament_brackets.id RESTRICT |
| `pool_id` | `ulid` | yes | FK tournament_pools.id; set only for pool-stage matches |
| `mat_id` | `ulid` | yes | FK venue_mats.id; assigned tatami |
| `bracket_section` | `tinyint` unsigned | no | 0=Main, 1=Repechage, 2=Bronze, 3=GoldFinal; default 0 |
| `round_number` | `tinyint` unsigned | no | |
| `round_name` | `varchar(50)` | no | e.g. `Round of 16`, `Semifinal`, `Final` |
| `match_number` | `tinyint` unsigned | no | position within round |
| `status` | `tinyint` unsigned | no | 0=Pending, 1=Active, 2=Completed; default 0 |
| `match_outcome` | `tinyint` unsigned | yes | 0=Ippon, 1=WazaAri, 2=Decision, 3=HansokuMake, 4=Walkover, 5=Bye, 6=GoldenScore |
| `is_golden_score` | `boolean` | no | default false |
| `winning_competitor_id` | `ulid` | yes | FK tournament_match_competitors.id (added in pass 2) |
| `golden_score_duration_seconds` | `unsigned integer` | yes | |
| `winner_next_position` | `tinyint` unsigned | yes | 0=White, 1=Blue — position in next match |
| `loser_next_position` | `tinyint` unsigned | yes | 0=White, 1=Blue |
| `winner_next_match_id` | `ulid` | yes | FK tournament_matches.id self-ref (added in pass 2) |
| `loser_next_match_id` | `ulid` | yes | FK tournament_matches.id self-ref (added in pass 2) |
| `scheduled_at` | `datetime` | yes | |
| `completed_at` | `datetime` | yes | |
| `notes` | `text` | yes | |
| *(EditorTrait columns)* | | | |

**FKs (pass 1):**
- `bracket_id` → `tournament_brackets.id` (RESTRICT / CASCADE)
- `pool_id` → `tournament_pools.id` (RESTRICT / CASCADE)
- `mat_id` → `venue_mats.id` (RESTRICT / CASCADE)

**FKs (pass 2 — `000027`):**
- `winning_competitor_id` → `tournament_match_competitors.id` (RESTRICT / CASCADE)
- `winner_next_match_id` → `tournament_matches.id` (RESTRICT / CASCADE) — self-reference
- `loser_next_match_id` → `tournament_matches.id` (RESTRICT / CASCADE) — self-reference

**Indexes:** `(bracket_id, bracket_section, round_number, match_number)` name `tm_bracket_position_index`, `status`, `scheduled_at`, `mat_id`

**CHECKs** (MySQL/PG only):
- `chk_tm_winner_pos` — `winner_next_position IS NULL OR winner_next_position IN (0,1)`
- `chk_tm_loser_pos` — `loser_next_position IS NULL OR loser_next_position IN (0,1)`

---

### `tournament_match_competitors` — `2026_01_01_000023`

The two competitors in a match, their position (White/Blue), scores, and result.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `match_id` | `ulid` | no | FK tournament_matches.id RESTRICT |
| `registration_id` | `ulid` | no | FK tournament_registrations.id RESTRICT |
| `position` | `tinyint` unsigned | no | 0=White (Shiro), 1=Blue (Ao) |
| `seed` | `tinyint` unsigned | yes | draw seeding number |
| `score` | `tinyint` unsigned | yes | Waza-ari count |
| `penalties` | `tinyint` unsigned | yes | Shido count |
| `result` | `tinyint` unsigned | no | 0=Pending, 1=Win, 2=Loss, 3=WalkoverWin, 4=Disqualified; default 0 |
| *(EditorTrait columns)* | | | |

**FKs:**
- `match_id` → `tournament_matches.id` (RESTRICT / CASCADE)
- `registration_id` → `tournament_registrations.id` (RESTRICT / CASCADE)

**Unique:**
- `(match_id, position, deleted_at)` — name `tmc_match_position_unique`
- `(match_id, registration_id, deleted_at)` — name `tmc_match_registration_unique`

---

### `tournament_match_referees` — `2026_01_01_000024`

Referee assignments to matches, by role.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `match_id` | `ulid` | no | FK tournament_matches.id RESTRICT |
| `referee_id` | `ulid` | no | FK referees.id RESTRICT |
| `role` | `tinyint` unsigned | no | 0=Center, 1=Side, 2=Video |
| *(EditorTrait columns)* | | | |

**FKs:**
- `match_id` → `tournament_matches.id` (RESTRICT / CASCADE)
- `referee_id` → `referees.id` (RESTRICT / CASCADE)

**Unique:** `(match_id, role, deleted_at)` — name `tmr_match_role_unique`

---

### `tournament_match_events` — `2026_01_01_000025`

Scoring and penalty events recorded during a match, attached to a competitor slot.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `match_competitor_id` | `ulid` | no | FK tournament_match_competitors.id RESTRICT |
| `event_type` | `tinyint` unsigned | no | 0=Ippon, 1=WazaAri, 2=Shido, 3=HansokuMake, 4=HansokuMakeLight, 5=Kinsa, 6=Note |
| `elapsed_seconds` | `unsigned integer` | no | seconds into the match |
| `occurred_at` | `datetime` | no | wall-clock time |
| `notes` | `varchar` | yes | |
| *(EditorTrait columns)* | | | |

**FKs:** `match_competitor_id` → `tournament_match_competitors.id` (RESTRICT / CASCADE)

**Indexes:** `(match_competitor_id, elapsed_seconds)` — name `tme_competitor_elapsed_index`

---

### `tournament_results` — `2026_01_01_000026`

Final medal / placement records for a category.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `ulid` | no | PK |
| `registration_id` | `ulid` | no | FK tournament_registrations.id RESTRICT |
| `position` | `tinyint` unsigned | no | 1=Gold, 2=Silver, 3=Bronze, 5=5th, 7=7th |
| *(EditorTrait columns)* | | | |

**FKs:** `registration_id` → `tournament_registrations.id` (RESTRICT / CASCADE)

**Unique:** `(registration_id, deleted_at)` — name `tres_registration_unique`

---

## 7. Authorization Tables (Spatie)

### `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` — `2026_05_22_151258`

Standard tables generated by [spatie/laravel-permission](https://spatie.be/docs/laravel-permission). Table names, column names, and guard are driven by `config/permission.php`.

**`permissions`**

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `bigint` auto-increment | no | PK |
| `name` | `varchar` | no | unique with guard_name |
| `guard_name` | `varchar` | no | |
| `created_at` / `updated_at` | `timestamp` | yes | |

**`roles`**

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | `bigint` auto-increment | no | PK |
| `name` | `varchar` | no | unique with guard_name |
| `guard_name` | `varchar` | no | |
| `created_at` / `updated_at` | `timestamp` | yes | |

**`model_has_permissions`** — pivot: model ↔ permission

| Column | Type |
|---|---|
| `permission_id` | `unsignedBigInteger` — FK permissions.id CASCADE |
| `model_type` | `varchar` |
| `model_id` | `unsignedBigInteger` |

PK: `(permission_id, model_id, model_type)`

**`model_has_roles`** — pivot: model ↔ role

| Column | Type |
|---|---|
| `role_id` | `unsignedBigInteger` — FK roles.id CASCADE |
| `model_type` | `varchar` |
| `model_id` | `unsignedBigInteger` |

PK: `(role_id, model_id, model_type)`

**`role_has_permissions`** — pivot: role ↔ permission

| Column | Type |
|---|---|
| `permission_id` | `unsignedBigInteger` — FK permissions.id CASCADE |
| `role_id` | `unsignedBigInteger` — FK roles.id CASCADE |

PK: `(permission_id, role_id)`

---

## Migration run order

```
0001_01_01_000000  users, password_reset_tokens, sessions
0001_01_01_000001  cache, cache_locks
0001_01_01_000002  jobs, job_batches, failed_jobs
0001_01_01_000003  audits
2026_01_01_000001  countries
2026_01_01_000002  belt_ranks
2026_01_01_000003  tournament_rule_sets
2026_01_01_000004  addresses
2026_01_01_000005  venues
2026_01_01_000006  venue_mats
2026_01_01_000007  clubs
2026_01_01_000008  players
2026_01_01_000009  clubs_players
2026_01_01_000010  referees
2026_01_01_000011  coaches
2026_01_01_000012  tournaments
2026_01_01_000013  tournament_categories
2026_01_01_000014  tournament_registrations
2026_01_01_000015  tournament_registration_fees
2026_01_01_000016  tournament_registration_payments
2026_01_01_000017  tournament_weigh_ins
2026_01_01_000018  tournament_registration_coaches
2026_01_01_000019  tournament_brackets
2026_01_01_000020  tournament_pools
2026_01_01_000021  tournament_pool_competitors
2026_01_01_000022  tournament_matches
2026_01_01_000023  tournament_match_competitors
2026_01_01_000024  tournament_match_referees
2026_01_01_000025  tournament_match_events
2026_01_01_000026  tournament_results
2026_01_01_000027  (ALTER tournament_matches — self-ref FKs)
2026_01_01_000028  clubs_coaches
2026_05_22_151258  permissions, roles, model_has_permissions, model_has_roles, role_has_permissions
```
