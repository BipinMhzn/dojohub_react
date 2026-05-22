# DojoHub — Fresh Database Architecture Plan

> **Scope**: Greenfield schema design for DojoHub, a martial arts tournament management system.
> No existing migrations, no backward-compatibility constraints.
> This document is the authoritative blueprint a fresh implementation should follow.

---

## Table of Contents

1. [Design Principles](#1-design-principles)
2. [Architecture Overview](#2-architecture-overview)
3. [Migration Execution Order](#3-migration-execution-order)
4. [Reference Tables](#4-reference-tables)
5. [Users & RBAC](#5-users--rbac) — `users`, `audits`
6. [Venues](#6-venues)
7. [Clubs & Players](#7-clubs--players)
8. [Tournament Hierarchy](#8-tournament-hierarchy)
9. [Operational Tables](#9-operational-tables)
10. [Financial Tables](#10-financial-tables)
11. [Enumerations](#11-enumerations)
12. [Indexes & Constraints](#12-indexes--constraints)
13. [Complete Schema Diagram](#13-complete-schema-diagram)
14. [Key Decisions vs Current Schema](#14-key-decisions-vs-current-schema)

---

## 1. Design Principles

| Principle | Decision |
|-----------|----------|
| **Primary keys** | ULID on all domain tables; `bigint` auto-increment on `users` only (Laravel convention) |
| **Soft deletes** | Every domain table carries `deleted_at`. Hard deletes are never issued in application code |
| **FK delete behaviour** | `RESTRICT` everywhere. Soft-delete issues `UPDATE`, not `DELETE`, so `RESTRICT` never fires in practice — but it guards against accidental hard-deletes |
| **Audit fields** | `created_by`, `updated_by`, `deleted_by` (→ `users.id`, nullable) via `EditorTrait` on every domain table |
| **Type safety** | Integer enums stored as `tinyint unsigned`, cast to PHP-backed enums in models |
| **Country codes** | ISO 3166-1 alpha-2 `char(2)` everywhere instead of free-text strings (`nationality`, `country`) |
| **Weight precision** | `decimal(5,2)` for all weight values (player, category bounds, weigh-in) — no `int` for weights |
| **Unique indexes** | Always composite with `deleted_at` to survive soft-delete + recreate cycles |
| **CHECK constraints** | Applied at DB level from day one for range bounds and position values |
| **RBAC** | `spatie/laravel-permission` roles from day one — not bolted on later |
| **Full audit log** | `owen-it/laravel-auditing` on every domain model — separate `audits` table records old/new JSON diffs per event. Complements `EditorTrait` (who owns the row) with tamper-evident history (what changed and when) |

---

## 2. Architecture Overview

```
belt_ranks (ref)          tournament_rule_sets (ref)     countries (ref)
     │                            │                            │
     └──► players.current_belt_rank_id  tournaments.rule_set_id  addresses.country_code
          clubs_players.belt_rank_id

users ──► roles/permissions (spatie)
  │
  ├──► referees.user_id (nullable)
  └──► coaches.user_id (nullable)

addresses ◄── venues.address_id (required)
          ◄── clubs.address_id (nullable)
          ◄── players.address_id (nullable)

venues
  └── venue_mats
  └── tournaments.venue_id
          └── tournament_categories
                    ├── tournament_registrations ◄── players, clubs
                    │         ├── tournament_weigh_ins
                    │         ├── tournament_registration_coaches ◄── coaches
                    │         ├── tournament_registration_payments
                    │         └── tournament_results
                    └── tournament_brackets
                              ├── tournament_pools
                              │     └── tournament_pool_competitors ◄── registrations
                              └── tournament_matches ◄── venue_mats
                                        ├── tournament_match_competitors ◄── registrations
                                        │         └── tournament_match_events
                                        └── tournament_match_referees ◄── referees
```

---

## 3. Migration Execution Order

Tables must be created in this order to satisfy FK constraints.

```
 1. users
 2. audits                              (owen-it/laravel-auditing — polymorphic, references users)
 3. countries                           (reference — seeded)
 4. belt_ranks                          (reference — seeded)
 5. tournament_rule_sets                (reference — seeded with IJF defaults)
 6. addresses
 7. venues
 8. venue_mats
 9. clubs
10. players
11. clubs_players
12. referees
13. coaches
14. tournaments
15. tournament_categories
16. tournament_registrations
17. tournament_registration_fees
18. tournament_registration_payments
19. tournament_weigh_ins
20. tournament_registration_coaches
21. tournament_brackets
22. tournament_pools
23. tournament_pool_competitors
24. tournament_matches                  (add self-ref FKs in a second pass — see note below)
25. tournament_match_competitors
26. tournament_match_referees
27. tournament_match_events
28. tournament_results

--- Second pass (self-referential FKs on tournament_matches) ---
29. Add winner_next_match_id, loser_next_match_id (self-ref)
30. Add winning_competitor_id → tournament_match_competitors.id
```

> **Self-referential FKs**: `tournament_matches` needs to reference itself (`winner_next_match_id`, `loser_next_match_id`) and reference `tournament_match_competitors` (`winning_competitor_id`), which is created after `tournament_matches`. Add these three columns in a separate migration after both tables exist.

---

## 4. Reference Tables

### 4.1 `countries`

Seeded with ISO 3166-1. UI uses this for all country dropdowns.

| Column | Type | Notes |
|--------|------|-------|
| code | char(2) PK | ISO 3166-1 alpha-2 (e.g. `NP`, `JP`) |
| name | string | Display name |

> No audit fields — this is a static reference table.

---

### 4.2 `belt_ranks`

Seeded with the 16 standard Judo belts (Rokkyu through Judan). Replaces the undocumented `tinyint` on `clubs_players`.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| code | string(10) UNIQUE | `6K`, `5K`, … `1K`, `1D`, … `10D` |
| name | string | `Rokkyu`, `Gokyu`, … `Shodan`, … `Judan` |
| kind | tinyint | `0=Kyu`, `1=Dan` |
| color | string(50) nullable | `White`, `Yellow`, `Orange`, `Green`, `Blue`, `Brown`, `Black` |
| sort_order | tinyint | Ascending from beginner to expert |

---

### 4.3 `tournament_rule_sets`

Seeded with `IJF 2024` defaults. Allows per-tournament customisation without code changes.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| name | string | `IJF 2024`, `Local Modified` |
| max_waza_ari | tinyint default 2 | Score cap — two Waza-ari = Ippon |
| max_shido | tinyint default 3 | Penalty limit — third Shido = Hansoku-make |
| match_duration_seconds | int default 240 | Standard fight time (4 min) |
| golden_score_seconds | int nullable | `null` = unlimited golden score |
| *audit* | | EditorTrait |

---

## 5. Users & RBAC

### 5.1 `users`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Auto-increment (Laravel convention) |
| name | string | |
| email | string UNIQUE | |
| email_verified_at | timestamp nullable | |
| password | string | Hashed |
| remember_token | string nullable | |
| created_by | bigint FK nullable | → users.id |
| updated_by | bigint FK nullable | → users.id |
| deleted_by | bigint FK nullable | → users.id |
| deleted_at | timestamp nullable | |
| created_at, updated_at | timestamps | |

**RBAC via `spatie/laravel-permission`** — adds `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.

**Application roles** (seeded):

| Role | Scope |
|------|-------|
| `super_admin` | Full system access |
| `tournament_manager` | Create/manage tournaments |
| `club_admin` | Manage own club + register players |
| `referee` | Record match results |
| `coach` | View registrations, accompany players |

---

### 5.2 `audits` (owen-it/laravel-auditing)

Managed entirely by the `owen-it/laravel-auditing` package. Every domain model that extends `AppBaseModel` automatically records a row here on `created`, `updated`, `deleted`, and `restored` events.

> **Do not write this migration by hand.** Run `php artisan auditing:install` after `composer require owen-it/laravel-auditing` — the package publishes and manages the migration.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Auto-increment (package convention) |
| user_type | string nullable | Morph type of the acting user (`App\Models\User`) |
| user_id | bigint nullable | → users.id — who made the change |
| event | string | `created`, `updated`, `deleted`, `restored` |
| auditable_type | string | Morph type of the changed model |
| auditable_id | string | PK of the changed model (string to support ULIDs) |
| old_values | text nullable | JSON snapshot of fields before the change |
| new_values | text nullable | JSON snapshot of fields after the change |
| url | text nullable | Request URL that triggered the event |
| ip_address | varchar(45) nullable | Client IP (IPv4 or IPv6) |
| user_agent | string nullable | Browser / client string |
| tags | string nullable | Optional label for bulk queries |
| created_at | timestamp nullable | When the event occurred |

**Integration with `AppBaseModel`**:

```php
// app/System/AppBaseModel.php
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditing;

abstract class AppBaseModel extends BaseModel implements Auditable
{
    use EditorLogs, Auditing, HasFactory, HasUlids, SoftDeletes;

    // Exclude EditorLogs columns — redundant with audits.user_id
    protected $auditExclude = ['created_by', 'updated_by', 'deleted_by'];
}
```

**Opt-out for static reference tables** (`countries`, `belt_ranks`, `tournament_rule_sets`) — these do not extend `AppBaseModel` and carry no `Auditing` trait.

**Relationship** — every domain model gains:

```php
$venue->audits;                              // all audit events
$venue->audits()->latest()->first();         // most recent event
$audit->old_values / $audit->new_values;    // before / after JSON
$audit->user;                               // the User who triggered the event
```

---

## 6. Venues

### 6.1 `addresses`

Shared address record. Owned by venues (required), clubs (optional), and players (optional home address).

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| address_line_1 | string | Street |
| address_line_2 | string nullable | Suite, building, etc. |
| city | string(100) | |
| state | string(100) nullable | Province / region |
| postal_code | string(20) nullable | |
| country_code | char(2) FK | → countries.code (restrict) |
| latitude | decimal(10,7) nullable | For venue map pins |
| longitude | decimal(10,7) nullable | |
| *audit* | | EditorTrait |

---

### 6.2 `venues`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| name | string | |
| address_id | ulid FK | → addresses.id (restrict) |
| *audit* | | EditorTrait |

> `mat_count` is removed — it is now derived from `COUNT(venue_mats)`.

---

### 6.3 `venue_mats`

Tatami mats as first-class entities. Eliminates the free-integer `mat_number` on matches.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| venue_id | ulid FK | → venues.id (restrict) |
| mat_number | tinyint | Sequential number within the venue |
| display_name | string nullable | `Tatami A`, `Mat 1` |
| *audit* | | EditorTrait |

**Constraints**: `UNIQUE (venue_id, mat_number, deleted_at)`

---

## 7. Clubs & Players

### 7.1 `clubs`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| name | string | |
| abbreviation | string(10) | Short code, e.g. `KJC`. Required |
| logo_url | string nullable | |
| address_id | ulid FK nullable | → addresses.id (restrict) |
| phone_number | string(20) nullable | |
| *audit* | | EditorTrait |

---

### 7.2 `players`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| name | string | |
| gender | tinyint | `0=Male`, `1=Female` |
| dob | date | Date of birth |
| weight | decimal(5,2) | Current weight in kg |
| nationality_code | char(2) FK nullable | → countries.code (restrict) |
| current_belt_rank_id | ulid FK nullable | → belt_ranks.id (restrict) |
| address_id | ulid FK nullable | → addresses.id (restrict) — home/contact address |
| email | string nullable | Contact email |
| profile_photo_url | string nullable | |
| phone_number | string(20) nullable | |
| *audit* | | EditorTrait |

---

### 7.3 `clubs_players`

Tracks membership history. A player may hold one active membership per club at a time (enforced at application level).

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| club_id | ulid FK | → clubs.id (restrict) |
| player_id | ulid FK | → players.id (restrict) |
| belt_rank_id | ulid FK nullable | → belt_ranks.id (restrict) — rank at time of joining |
| grade_certificate | string(100) nullable | Certificate name / award label |
| started_at | datetime | When the player joined |
| ended_at | datetime nullable | Null = currently active |
| *audit* | | EditorTrait |

**Indexes**: `(club_id, player_id)`, `(player_id)`, `(club_id)`

---

### 7.4 `referees`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| user_id | bigint FK nullable | → users.id (restrict) — a referee may not have a login |
| full_name | string | |
| nationality_code | char(2) FK nullable | → countries.code (restrict) |
| certification_level | tinyint nullable | `0=Club`, `1=National`, `2=Continental`, `3=International` |
| *audit* | | EditorTrait |

---

### 7.5 `coaches`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| user_id | bigint FK nullable | → users.id (restrict) |
| full_name | string | |
| club_id | ulid FK nullable | → clubs.id (restrict) — home club |
| *audit* | | EditorTrait |

---

## 8. Tournament Hierarchy

### 8.1 `tournaments`

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| name | string | e.g. `Kathmandu Open 2026` |
| description | text nullable | |
| logo_url | string nullable | |
| level | tinyint | See [Tournament Level](#tournament-level) |
| status | tinyint | See [Tournament Status](#tournament-status) |
| venue_id | ulid FK | → venues.id (restrict) |
| rule_set_id | ulid FK nullable | → tournament_rule_sets.id (restrict) |
| timezone | string(64) nullable | IANA timezone e.g. `Asia/Kathmandu` |
| start_at | datetime | |
| end_at | datetime nullable | |
| *audit* | | EditorTrait |

**CHECK constraints**:
- `end_at IS NULL OR start_at <= end_at`

**Indexes**: `(venue_id)`, `(status)`, `(start_at)`

---

### 8.2 `tournament_categories`

A competitive division (e.g. Male Cadets -73 kg).

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| tournament_id | ulid FK | → tournaments.id (restrict) |
| name | string nullable | Auto-generated from enum labels if null |
| gender | tinyint | `0=Male`, `1=Female` |
| age_category | tinyint | See [Age Category](#age-category) |
| min_age | tinyint | Inclusive minimum (years). Seeded from enum, manager-adjustable |
| max_age | tinyint | Inclusive maximum (years) |
| weight_category | tinyint | See [Weight Category](#weight-category) |
| min_weight | decimal(5,2) | Inclusive minimum (kg) |
| max_weight | decimal(5,2) | Inclusive maximum (kg) |
| status | tinyint | See [Category Status](#category-status) |
| *audit* | | EditorTrait |

**CHECK constraints**:
- `min_age <= max_age`
- `min_weight <= max_weight`

**Unique**: `(tournament_id, gender, age_category, weight_category, deleted_at)` as `tc_division_unique`

**Indexes**: `(tournament_id)`, `(status)`

---

### 8.3 `tournament_registrations`

Player enrollment into a category. The snapshot record that travels through the entire tournament.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| tournament_category_id | ulid FK | → tournament_categories.id (restrict) |
| club_id | ulid FK | → clubs.id (restrict) — club representing the player |
| player_id | ulid FK | → players.id (restrict) |
| player_age | tinyint unsigned | Age at time of registration |
| player_weight | decimal(5,2) | Weight at registration (in kg) |
| status | tinyint | See [Registration Status](#registration-status) |
| *audit* | | EditorTrait |

**Unique**: `(tournament_category_id, player_id, deleted_at)`

**Indexes**: `(player_id)`, `(club_id)`, `(status)`

---

### 8.4 `tournament_brackets`

Competition structure for one category.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| tournament_category_id | ulid FK | → tournament_categories.id (restrict) |
| type | tinyint | See [Bracket Type](#bracket-type) |
| seeding_method | tinyint | See [Seeding Method](#seeding-method) |
| competitor_count | tinyint nullable | Set on generation |
| round_count | tinyint nullable | Set on generation |
| status | tinyint | See [Bracket Status](#bracket-status) |
| generated_at | datetime nullable | When bracket was generated |
| *audit* | | EditorTrait |

**Indexes**: `(tournament_category_id)`

---

### 8.5 `tournament_pools`

Round-robin pools within a Pool+Final bracket.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| bracket_id | ulid FK | → tournament_brackets.id (restrict) |
| name | string(50) | `Pool A`, `Pool B` |
| *audit* | | EditorTrait |

---

### 8.6 `tournament_pool_competitors`

Which registrations compete in which pool.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| pool_id | ulid FK | → tournament_pools.id (restrict) |
| registration_id | ulid FK | → tournament_registrations.id (restrict) |
| finishing_position | tinyint nullable | Pool-stage finishing rank |
| *audit* | | EditorTrait |

**Unique**: `(pool_id, registration_id, deleted_at)`

---

### 8.7 `tournament_matches`

One fight in a bracket. Does **not** store competitors — they live in `tournament_match_competitors`.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| bracket_id | ulid FK | → tournament_brackets.id (restrict) |
| pool_id | ulid FK nullable | → tournament_pools.id (restrict) — set only for pool-stage matches |
| mat_id | ulid FK nullable | → venue_mats.id (restrict) — assigned tatami mat |
| bracket_section | tinyint | See [Bracket Section](#bracket-section) |
| round_number | tinyint | 1 = first round |
| round_name | string(50) | `Round of 16`, `Quarterfinal`, `Semifinal`, `Final` |
| match_number | tinyint | 1-based position within the round |
| status | tinyint | See [Match Status](#match-status) |
| match_outcome | tinyint nullable | See [Match Outcome](#match-outcome) — set on completion |
| is_golden_score | boolean default false | |
| golden_score_duration_seconds | int nullable | |
| winning_competitor_id | ulid FK nullable | → tournament_match_competitors.id (restrict). Added in migration pass 2 |
| winner_next_match_id | ulid FK nullable | → tournament_matches.id (restrict). Self-ref. Added in pass 2 |
| winner_next_position | tinyint nullable | `0=White`, `1=Blue` (CompetitorPositionEnum) |
| loser_next_match_id | ulid FK nullable | → tournament_matches.id (restrict). Self-ref. Added in pass 2 |
| loser_next_position | tinyint nullable | `0=White`, `1=Blue` (CompetitorPositionEnum) |
| scheduled_at | datetime nullable | |
| completed_at | datetime nullable | |
| notes | text nullable | Referee notes, DQ reason |
| *audit* | | EditorTrait |

**CHECK constraints**:
- `winner_next_position IS NULL OR winner_next_position IN (0,1)`
- `loser_next_position IS NULL OR loser_next_position IN (0,1)`

**Indexes**: `(bracket_id, bracket_section, round_number, match_number)`, `(status)`, `(scheduled_at)`, `(mat_id)`

---

### 8.8 `tournament_match_competitors`

Per-match record for each athlete (exactly two rows per match).

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| match_id | ulid FK | → tournament_matches.id (restrict) |
| registration_id | ulid FK | → tournament_registrations.id (restrict) |
| position | tinyint | `0=White`, `1=Blue` |
| seed | tinyint nullable | Seeding rank in the bracket |
| score | tinyint nullable | Waza-ari count. Validated against `rule_set.max_waza_ari` |
| penalties | tinyint nullable | Shido count. Validated against `rule_set.max_shido` |
| result | tinyint default 0 | See [Competitor Result](#competitor-result) |
| *audit* | | EditorTrait |

**Unique**: `(match_id, position, deleted_at)`, `(match_id, registration_id, deleted_at)`

> `score` and `penalties` are no longer CHECK-constrained at DB level — their bounds depend on the tournament's `rule_set`. Validation is application-level, reading from `tournament_rule_sets`.

---

### 8.9 `tournament_results`

Final finishing positions per category.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| registration_id | ulid FK | → tournament_registrations.id (restrict) |
| position | tinyint | `1=Gold`, `2=Silver`, `3=Bronze`, `5=5th`, `7=7th` |
| *audit* | | EditorTrait |

**Unique**: `(registration_id, deleted_at)`

---

## 9. Operational Tables

### 9.1 `tournament_match_referees`

Which referees officiate which matches.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| match_id | ulid FK | → tournament_matches.id (restrict) |
| referee_id | ulid FK | → referees.id (restrict) |
| role | tinyint | `0=Center`, `1=Side`, `2=Video` |
| *audit* | | EditorTrait |

**Unique**: `(match_id, role, deleted_at)` — one referee per role per match

---

### 9.2 `tournament_match_events`

Live scoring event log. Enables match replay, time-stamped attribution, and derived score computation.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| match_competitor_id | ulid FK | → tournament_match_competitors.id (restrict) |
| event_type | tinyint | See [Match Event Type](#match-event-type) |
| elapsed_seconds | int unsigned | Time on match clock when event occurred |
| occurred_at | datetime | Wall-clock timestamp |
| notes | string nullable | |
| *audit* | | EditorTrait |

**Indexes**: `(match_competitor_id, elapsed_seconds)`

---

### 9.3 `tournament_weigh_ins`

Official weigh-in records. Provides provenance for `tournament_registrations.player_weight`.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| registration_id | ulid FK | → tournament_registrations.id (restrict) |
| weight | decimal(5,2) | Measured weight in kg |
| weighed_at | datetime | |
| weighed_by | bigint FK nullable | → users.id (restrict) — official who recorded it |
| made_weight | boolean | Denormalised: weight within category bounds at time of weigh-in |
| notes | string nullable | |
| *audit* | | EditorTrait |

**Indexes**: `(registration_id, weighed_at)`

---

### 9.4 `tournament_registration_coaches`

Coach assignments per registration (player's corner team).

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| registration_id | ulid FK | → tournament_registrations.id (restrict) |
| coach_id | ulid FK | → coaches.id (restrict) |
| *audit* | | EditorTrait |

**Unique**: `(registration_id, coach_id, deleted_at)`

---

## 10. Financial Tables

### 10.1 `tournament_registration_fees`

Fee schedule per category.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| tournament_category_id | ulid FK | → tournament_categories.id (restrict) |
| amount | decimal(10,2) unsigned | |
| currency | char(3) | ISO 4217 e.g. `NPR`, `USD` |
| *audit* | | EditorTrait |

---

### 10.2 `tournament_registration_payments`

Payment records for each registration.

| Column | Type | Notes |
|--------|------|-------|
| id | ulid PK | |
| registration_id | ulid FK | → tournament_registrations.id (restrict) |
| amount_paid | decimal(10,2) | |
| currency | char(3) | ISO 4217 |
| paid_at | datetime nullable | |
| method | tinyint | `0=Cash`, `1=Bank`, `2=Online` |
| reference | string nullable | Bank ref, transaction ID |
| *audit* | | EditorTrait |

**Indexes**: `(registration_id)`

---

## 11. Enumerations

All stored as `tinyint unsigned` unless noted. PHP-backed enums use TitleCase cases.

### Gender
| Value | Label |
|-------|-------|
| 0 | Male |
| 1 | Female |

### Tournament Level
| Value | Label |
|-------|-------|
| 1 | Local |
| 2 | Regional |
| 3 | National |
| 4 | International |

### Tournament Status
| Value | Label |
|-------|-------|
| 0 | Scheduled |
| 1 | InProgress |
| 2 | Completed |
| 3 | Cancelled |

### Category Status
| Value | Label |
|-------|-------|
| 0 | Pending |
| 1 | Seeding |
| 2 | Active |
| 3 | Completed |

### Registration Status
| Value | Label | Notes |
|-------|-------|-------|
| 0 | Registered | Default |
| 1 | Confirmed | Bracket generation imminent |
| 2 | Withdrawn | Excluded from bracket seeding |

### Age Category
| Value | Label | Default Range |
|-------|-------|---------------|
| 0 | Preparation | 1–6 |
| 1 | SuperMini | 7–8 |
| 2 | Mini | 9–10 |
| 3 | Pupils | 11–12 |
| 4 | Juveniles | 13–14 |
| 5 | Cadets | 15–17 |
| 6 | Juniors | 18–20 |
| 7 | Under23 | 15–22 |
| 8 | Seniors | 21–30 |
| 9 | Veterans | 31–99 |

### Weight Category
| Value | Label | Gender | Default Range (kg) |
|-------|-------|--------|-------------------|
| 0 | Minus48kg | Female | 0–48.00 |
| 1 | Minus52kg | Female | 48.01–52.00 |
| 2 | Minus57kg | Female | 52.01–57.00 |
| 3 | Minus60kg | Male | 0–60.00 |
| 4 | Minus63kg | Female | 57.01–63.00 |
| 5 | Minus66kg | Male | 60.01–66.00 |
| 6 | Minus70kg | Female | 63.01–70.00 |
| 7 | Minus73kg | Male | 66.01–73.00 |
| 8 | Minus78kg | Female | 70.01–78.00 |
| 9 | Minus81kg | Male | 73.01–81.00 |
| 10 | Minus90kg | Male | 81.01–90.00 |
| 11 | Minus100kg | Male | 90.01–100.00 |
| 12 | Plus100kg | Male | 100.01–999.99 |
| 13 | Plus78kg | Female | 78.01–999.99 |

> Gender column is a reference guide only — not enforced at DB level.

### Bracket Type
| Value | Label |
|-------|-------|
| 0 | SingleElimination |
| 1 | DoubleElimination |
| 2 | PoolFinal |
| 3 | RepechageIJF |

### Bracket Status
| Value | Label |
|-------|-------|
| 0 | Pending |
| 1 | Active |
| 2 | Completed |

### Bracket Section
| Value | Label |
|-------|-------|
| 0 | Main |
| 1 | Repechage |
| 2 | Bronze |
| 3 | GoldFinal |

### Match Status
| Value | Label |
|-------|-------|
| 0 | Pending |
| 1 | Active |
| 2 | Completed |

### Match Outcome
| Value | Label | Notes |
|-------|-------|-------|
| 0 | Ippon | Win by full score |
| 1 | WazaAri | Decision via two waza-ari |
| 2 | Decision | Judge's decision / points |
| 3 | HansokuMake | Disqualification |
| 4 | Walkover | Opponent withdrew/absent |
| 5 | Bye | No opponent — automatic advance |
| 6 | GoldenScore | Decided in extra time |

### Match Event Type
| Value | Label |
|-------|-------|
| 0 | Ippon |
| 1 | WazaAri |
| 2 | Shido |
| 3 | HansokuMake |
| 4 | HansokuMakeLight |
| 5 | Kinsa |
| 6 | Note |

### Competitor Position
| Value | Label |
|-------|-------|
| 0 | White |
| 1 | Blue |

### Competitor Result
| Value | Label |
|-------|-------|
| 0 | Pending |
| 1 | Win |
| 2 | Loss |
| 3 | WalkoverWin |
| 4 | Disqualified |

### Seeding Method
| Value | Label |
|-------|-------|
| 0 | Random |
| 1 | Manual |
| 2 | ByRank |

### Referee Role
| Value | Label |
|-------|-------|
| 0 | Center |
| 1 | Side |
| 2 | Video |

### Referee Certification Level
| Value | Label |
|-------|-------|
| 0 | Club |
| 1 | National |
| 2 | Continental |
| 3 | International |

### Payment Method
| Value | Label |
|-------|-------|
| 0 | Cash |
| 1 | Bank |
| 2 | Online |

---

## 12. Indexes & Constraints

### Soft-delete-safe unique indexes

All unique indexes are composite with `deleted_at`. This allows a soft-deleted row to be recreated with the same natural key without collision.

```sql
-- tournament_categories
UNIQUE (tournament_id, gender, age_category, weight_category, deleted_at) AS tc_division_unique

-- tournament_registrations
UNIQUE (tournament_category_id, player_id, deleted_at)

-- tournament_match_competitors
UNIQUE (match_id, position, deleted_at)
UNIQUE (match_id, registration_id, deleted_at)

-- tournament_results
UNIQUE (registration_id, deleted_at)

-- tournament_pool_competitors
UNIQUE (pool_id, registration_id, deleted_at)

-- venue_mats
UNIQUE (venue_id, mat_number, deleted_at)

-- tournament_match_referees
UNIQUE (match_id, role, deleted_at)

-- tournament_registration_coaches
UNIQUE (registration_id, coach_id, deleted_at)
```

### CHECK constraints

```sql
-- tournament_categories
CHECK (min_age <= max_age)
CHECK (min_weight <= max_weight)

-- tournaments
CHECK (end_at IS NULL OR start_at <= end_at)

-- clubs_players
CHECK (ended_at IS NULL OR started_at <= ended_at)

-- tournament_matches
CHECK (winner_next_position IS NULL OR winner_next_position IN (0,1))
CHECK (loser_next_position IS NULL OR loser_next_position IN (0,1))
```

---

## 13. Complete Schema Diagram

```
users
  id (bigint PK)
  name, email, password
  created_by → users.id (nullable)
  updated_by → users.id (nullable)
  deleted_by → users.id (nullable)

audits  [owen-it/laravel-auditing — package-managed]
  id (bigint PK)
  user_type, user_id → users.id (nullable morph)
  event (created / updated / deleted / restored)
  auditable_type, auditable_id (morph to any domain model)
  old_values (JSON), new_values (JSON)
  url, ip_address, user_agent, tags
  created_at

countries (ref)
  code (char(2) PK)
  name

belt_ranks (ref)
  id (ulid PK)
  code (UNIQUE), name, kind, color, sort_order

tournament_rule_sets (ref)
  id (ulid PK)
  name, max_waza_ari, max_shido, match_duration_seconds, golden_score_seconds

addresses
  id (ulid PK)
  address_line_1, address_line_2, city, state, postal_code
  country_code → countries.code
  latitude, longitude

venues
  id (ulid PK)
  name
  address_id → addresses.id (RESTRICT)

venue_mats
  id (ulid PK)
  venue_id → venues.id (RESTRICT)
  mat_number, display_name
  UNIQUE (venue_id, mat_number, deleted_at)

clubs
  id (ulid PK)
  name, abbreviation, logo_url, phone_number
  address_id → addresses.id (RESTRICT, nullable)

players
  id (ulid PK)
  name, gender, dob, weight
  nationality_code → countries.code (nullable)
  current_belt_rank_id → belt_ranks.id (nullable)
  address_id → addresses.id (nullable)
  email, profile_photo_url, phone_number

clubs_players
  id (ulid PK)
  club_id → clubs.id (RESTRICT)
  player_id → players.id (RESTRICT)
  belt_rank_id → belt_ranks.id (nullable)
  grade_certificate, started_at, ended_at
  INDEX (club_id, player_id), (player_id)

referees
  id (ulid PK)
  user_id → users.id (nullable)
  full_name
  nationality_code → countries.code (nullable)
  certification_level

coaches
  id (ulid PK)
  user_id → users.id (nullable)
  full_name
  club_id → clubs.id (nullable)

tournaments
  id (ulid PK)
  name, description, logo_url
  level, status
  venue_id → venues.id (RESTRICT)
  rule_set_id → tournament_rule_sets.id (nullable)
  timezone, start_at, end_at
  CHECK (end_at IS NULL OR start_at <= end_at)

tournament_categories
  id (ulid PK)
  tournament_id → tournaments.id (RESTRICT)
  name (nullable), gender
  age_category, min_age, max_age
  weight_category, min_weight (decimal), max_weight (decimal)
  status
  UNIQUE (tournament_id, gender, age_category, weight_category, deleted_at)
  CHECK (min_age <= max_age), CHECK (min_weight <= max_weight)

tournament_registrations
  id (ulid PK)
  tournament_category_id → tournament_categories.id (RESTRICT)
  club_id → clubs.id (RESTRICT)
  player_id → players.id (RESTRICT)
  player_age, player_weight (decimal)
  status
  UNIQUE (tournament_category_id, player_id, deleted_at)

tournament_registration_fees
  id (ulid PK)
  tournament_category_id → tournament_categories.id (RESTRICT)
  amount, currency

tournament_registration_payments
  id (ulid PK)
  registration_id → tournament_registrations.id (RESTRICT)
  amount_paid, currency, paid_at, method, reference

tournament_weigh_ins
  id (ulid PK)
  registration_id → tournament_registrations.id (RESTRICT)
  weight (decimal), weighed_at, made_weight
  weighed_by → users.id (nullable)
  INDEX (registration_id, weighed_at)

tournament_registration_coaches
  id (ulid PK)
  registration_id → tournament_registrations.id (RESTRICT)
  coach_id → coaches.id (RESTRICT)
  UNIQUE (registration_id, coach_id, deleted_at)

tournament_brackets
  id (ulid PK)
  tournament_category_id → tournament_categories.id (RESTRICT)
  type, seeding_method
  competitor_count, round_count, status, generated_at

tournament_pools
  id (ulid PK)
  bracket_id → tournament_brackets.id (RESTRICT)
  name

tournament_pool_competitors
  id (ulid PK)
  pool_id → tournament_pools.id (RESTRICT)
  registration_id → tournament_registrations.id (RESTRICT)
  finishing_position
  UNIQUE (pool_id, registration_id, deleted_at)

tournament_matches
  id (ulid PK)
  bracket_id → tournament_brackets.id (RESTRICT)
  pool_id → tournament_pools.id (nullable)
  mat_id → venue_mats.id (nullable)
  bracket_section, round_number, round_name, match_number
  status, match_outcome
  is_golden_score, golden_score_duration_seconds
  winning_competitor_id → tournament_match_competitors.id (nullable) [pass 2]
  winner_next_match_id → tournament_matches.id (nullable) [pass 2, self-ref]
  winner_next_position (0/1)
  loser_next_match_id → tournament_matches.id (nullable) [pass 2, self-ref]
  loser_next_position (0/1)
  scheduled_at, completed_at, notes
  INDEX (bracket_id, bracket_section, round_number, match_number)
  CHECK (winner_next_position IN (0,1) OR IS NULL)
  CHECK (loser_next_position IN (0,1) OR IS NULL)

tournament_match_competitors
  id (ulid PK)
  match_id → tournament_matches.id (RESTRICT)
  registration_id → tournament_registrations.id (RESTRICT)
  position (0=White / 1=Blue)
  seed, score, penalties, result
  UNIQUE (match_id, position, deleted_at)
  UNIQUE (match_id, registration_id, deleted_at)

tournament_match_referees
  id (ulid PK)
  match_id → tournament_matches.id (RESTRICT)
  referee_id → referees.id (RESTRICT)
  role (0=Center / 1=Side / 2=Video)
  UNIQUE (match_id, role, deleted_at)

tournament_match_events
  id (ulid PK)
  match_competitor_id → tournament_match_competitors.id (RESTRICT)
  event_type, elapsed_seconds, occurred_at, notes
  INDEX (match_competitor_id, elapsed_seconds)

tournament_results
  id (ulid PK)
  registration_id → tournament_registrations.id (RESTRICT)
  position (1=Gold / 2=Silver / 3=Bronze / 5=5th / 7=7th)
  UNIQUE (registration_id, deleted_at)
```

---

## 14. Key Decisions vs Current Schema

| # | Topic | Current Schema | Fresh Design | Reason |
|---|-------|---------------|--------------|--------|
| 1 | **Match winner** | `winner_id → players.id` — violates "no player on match" rule | `winning_competitor_id → tournament_match_competitors.id` | Winner must be one of the two competitors in that match |
| 2 | **Mat assignment** | `mat_number tinyint` (free integer, no FK) | `mat_id → venue_mats.id` | Prevents scheduling on a mat that doesn't exist at the venue |
| 3 | **Mat count** | `venues.mat_count` stored column | Derived: `COUNT(venue_mats)` | Single source of truth, no drift |
| 4 | **Country/nationality** | Free-text `string(100)` | `country_code char(2)` + `countries` reference table | Enables consistent filtering, sorting, and UI dropdowns |
| 5 | **Weight bounds** | `min_weight / max_weight` as `int` | `decimal(5,2)` | Registration uses `decimal` — bounds must match to avoid half-kilogram edge cases |
| 6 | **Belt rank** | Free `tinyint` on `clubs_players`, no enum, undocumented `grade` column | `belt_ranks` reference table; FK on `clubs_players` and `players` | Named belts, sortable, color-coded, extensible |
| 7 | **Rule sets** | Score limits hardcoded in comments (`score 0–2`, `penalties 0–3`) | `tournament_rule_sets` table with `max_waza_ari`, `max_shido`, etc. | Different federations run different rules; no code changes needed |
| 8 | **Referees** | Lives in `tournament_matches.notes` (free text) | `referees` table + `tournament_match_referees` pivot | First-class entities — searchable, assignable, certifiable |
| 9 | **Coaches** | Not modelled | `coaches` + `tournament_registration_coaches` | Corner team is domain-relevant |
| 10 | **Live scoring** | Only final score/penalties stored | `tournament_match_events` table | Match replay, time-stamped attribution, per-event undo |
| 11 | **Weigh-in** | Weight snapshot in `tournament_registrations` only | `tournament_weigh_ins` table | Provenance, official timestamp, multi-attempt history |
| 12 | **Match outcome type** | Buried in `notes` (free text) | `match_outcome` enum column | Structured; enables filtering by Ippon/Decision/etc. |
| 13 | **Golden score** | No first-class support | `is_golden_score boolean` + `golden_score_duration_seconds` | Common match condition; should be queryable |
| 14 | **Pool stage** | `BracketTypeEnum::PoolFinal` exists but no schema support | `tournament_pools` + `tournament_pool_competitors` + `pool_id` on matches | Schema matches the claimed feature |
| 15 | **Payments** | Not modelled | `tournament_registration_fees` + `tournament_registration_payments` | Basic financial tracking for entry fees |
| 16 | **RBAC** | Flat `users` table | `spatie/laravel-permission` roles from day one | Tournament Manager, Club Admin, Referee, Coach separation |
| 17 | **Unique indexes** | Ignore `deleted_at` — collide on soft-delete + recreate | Composite with `deleted_at` | Correctness under the soft-delete lifecycle |
| 18 | **CHECK constraints** | Absent | Applied from day one on age/weight bounds and position enums | DB-level guard against bad data |
| 19 | **Position enum on next-match** | Documented as `1 or 2` (inconsistent with `0=White/1=Blue`) | `CompetitorPositionEnum` (0/1) everywhere | Single enum, no translation layer needed |
| 20 | **Bracket section naming** | `BracketSectionEnum::Final` (ambiguous with `BracketType::PoolFinal`) | `GoldFinal` | Eliminates naming confusion in code |
| 21 | **Timezone** | Not stored | `tournaments.timezone` (IANA string) | Scheduled matches render in local time for on-site display |
| 22 | **Player address** | Not modelled | `players.address_id` (nullable FK → addresses) | Contact address for correspondence and weigh-in location lookup |
| 23 | **Full change history** | No audit trail — only `created_by`/`updated_by`/`deleted_by` on each row | `owen-it/laravel-auditing` — separate `audits` table records old/new JSON diffs per event for every domain model | `EditorTrait` tells you *who owns the row*; `laravel-auditing` tells you *what changed and when* — required for dispute resolution, rollback, and compliance |
