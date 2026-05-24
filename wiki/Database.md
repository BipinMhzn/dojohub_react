# DojoHub – Database Schema Wiki

## Overview

This document is the authoritative reference for the DojoHub Judo Tournament Management System database schema.
It covers all tables, column definitions, relationships, enumeration values, and migration strategy.

> **Primary Key Convention**: All domain tables use ULID primary keys (except `users` which uses a standard auto-increment integer for Laravel compatibility).
> **Audit Convention**: All domain tables include `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at` via `EditorTrait`. The `users` table also carries these fields but they are defined manually (not via EditorTrait).

---

## Table of Contents

1. [Core Hierarchy](#core-hierarchy)
2. [Entity Relationship Overview](#entity-relationship-overview)
3. [Tables](#tables)
   - [users](#1-users)
   - [addresses](#2-addresses)
   - [venues](#3-venues)
   - [clubs](#4-clubs)
   - [players](#5-players)
   - [clubs_players](#6-clubs_players)
   - [tournaments](#7-tournaments)
   - [tournament_categories](#8-tournament_categories)
   - [tournament_registrations](#9-tournament_registrations)
   - [tournament_registration_fees](#10-tournament_registration_fees)
   - [tournament_registration_payments](#11-tournament_registration_payments)
   - [tournament_weigh_ins](#12-tournament_weigh_ins)
   - [tournament_registration_coaches](#13-tournament_registration_coaches)
   - [tournament_brackets](#14-tournament_brackets)
   - [tournament_pools](#15-tournament_pools)
   - [tournament_pool_competitors](#16-tournament_pool_competitors)
   - [tournament_matches](#17-tournament_matches)
   - [tournament_match_competitors](#18-tournament_match_competitors)
   - [tournament_match_referees](#19-tournament_match_referees)
   - [tournament_match_events](#20-tournament_match_events)
   - [tournament_results](#21-tournament_results)
4. [Enumeration Values](#enumeration-values)
   - [Gender](#gender)
   - [Tournament Level](#tournament-level)
   - [Tournament Status](#tournament-status)
   - [Category Status](#category-status)
   - [Registration Status](#registration-status)
   - [Age Category](#age-category)
   - [Weight Category](#weight-category)
   - [Seeding Method](#seeding-method)
   - [Bracket Type](#bracket-type)
   - [Bracket Status](#bracket-status)
   - [Bracket Section](#bracket-section)
   - [Match Status](#match-status)
   - [Competitor Position](#competitor-position)
   - [Competitor Result](#competitor-result)
5. [Migrations](#migrations)
6. [Complete Schema Diagram](#complete-schema-diagram)

---

## Core Hierarchy

Each layer has a distinct responsibility.

```
Tournament
   └── Tournament Categories
               └── Tournament Brackets
                         └── Tournament Matches
                                   └── Tournament Match Competitors
```

| Layer                       | Responsibility                                           |
|-----------------------------|----------------------------------------------------------|
| Tournament                  | Event container (name, date, venue)                      |
| Tournament Category         | Competitive division (gender + age + weight class)       |
| Tournament Bracket          | Competition structure for one division                   |
| Tournament Match            | Individual fight within a bracket                        |
| Tournament Match Competitor | Athlete's record within a specific match (score, result) |

Additional supporting tables:

| Table                    | Responsibility                                  |
|--------------------------|-------------------------------------------------|
| Tournament Registrations | Player enrollment into a category               |
| Tournament Results       | Final finishing positions per category          |

---

## Entity Relationship Overview

```
users
  └── (created_by / updated_by / deleted_by audit on all tables including users itself)

addresses
  └── venues.address_id (required)
  └── clubs.address_id  (nullable)

venues
  └── tournaments.venue_id

clubs ──────────────────────────────────────────────┐
  └── clubs_players.club_id                         │
  └── tournament_registrations.club_id              │
                                                    │
players ────────────────────────────────────────────┤
  └── clubs_players.player_id                       │
  └── tournament_registrations.player_id            │
  └── tournament_matches.winner_id                  │

tournaments
  └── tournament_categories.tournament_id
          └── tournament_registrations.tournament_category_id ──► players + clubs
          └── tournament_brackets.tournament_category_id
                    └── tournament_matches.bracket_id
                              └── tournament_match_competitors.match_id ─► tournament_registrations
                              (self-ref) winner_next_match_id → tournament_matches.id
                              (self-ref) loser_next_match_id  → tournament_matches.id
```

---

## Tables

---

### 1. `users`

Authentication table. Uses auto-increment integer PK (not ULID). Audit fields are defined manually — not via EditorTrait.

| Column            | Type             | Notes                                                              |
|-------------------|------------------|--------------------------------------------------------------------|
| id                | bigint (PK)      | Auto-increment                                                     |
| name              | string           |                                                                    |
| email             | string           | Unique                                                             |
| email_verified_at | timestamp        | Nullable                                                           |
| password          | string           | Hashed                                                             |
| remember_token    | string           | Nullable                                                           |
| created_by        | bigint (FK)      | Nullable — → users.id. Null for self-registered or first admin     |
| updated_by        | bigint (FK)      | Nullable — → users.id                                              |
| deleted_by        | bigint (FK)      | Nullable — → users.id. Set when account is soft-deleted            |
| deleted_at        | timestamp        | Nullable — soft delete timestamp                                   |
| created_at        | timestamp        |                                                                    |
| updated_at        | timestamp        |                                                                    |

**Related tables**: `password_reset_tokens`, `sessions`

---

### 2. `addresses`

Reusable location record shared by `venues` and `clubs`. Centralises address data to avoid repetition across tables.

| Column  | Type        | Notes                          |
|---------|-------------|--------------------------------|
| id      | ulid (PK)   |                                |
| address | string      | Street address line            |
| city    | string(100) |                                |
| state   | string(100) |                                |
| country | string(100) |                                |
| *audit* |             | EditorTrait                    |

**Usage**:
- `venues.address_id` — required (a venue always has a physical location)
- `clubs.address_id` — nullable (a club may not have a formal address)

**Lifecycle**: Address records are created and deleted alongside their owning entity (venue or club). Managed at the application level since MySQL does not support cascading deletes in the reverse direction (parent deleting child).

---

### 3. `venues`

Physical locations where tournaments are held.

| Column     | Type      | Notes                                                      |
|------------|-----------|------------------------------------------------------------|
| id         | ulid (PK) |                                                            |
| name       | string    |                                                            |
| address_id | ulid (FK) | → addresses.id (restrict)                                          |
| mat_count  | tinyint   | Number of tatami mats available. Default 1. Used for match scheduling |
| *audit*    |           | EditorTrait                                                |

---

### 4. `clubs`

Martial arts clubs / dojos that manage players.

| Column       | Type      | Notes                                     |
|--------------|-----------|-------------------------------------------|
| id           | ulid (PK) |                                           |
| name         | string    |                                           |
| abbreviation | string(10)| Short club code (e.g. KJC)               |
| logo_url     | string    | Nullable                                  |
| address_id   | ulid (FK) | Nullable — → addresses.id (set null)      |
| phone_number | string(20)| Nullable                                  |
| *audit*      |           | EditorTrait                               |

---

### 5. `players`

Individual athletes registered in the system.

| Column            | Type         | Notes                                                            |
|-------------------|--------------|------------------------------------------------------------------|
| id                | ulid (PK)    |                                                                  |
| name              | string       |                                                                  |
| gender            | tinyint      | See [Gender](#gender)                                            |
| dob               | date         | Date of birth                                                    |
| weight            | decimal(5,2) | Current weight in kg (snapshot taken again at registration time) |
| nationality       | string(100)  | Nullable — country the player competes for (may differ from club location) |
| email             | string       | Nullable — contact email                                         |
| profile_photo_url | string       | Nullable                                                         |
| phone_number      | string(20)   | Nullable                                                         |
| *audit*           |              | EditorTrait                                                      |

---

### 6. `clubs_players`

Tracks a player's club membership history. A player may belong to multiple clubs over time, and may even rejoin the same club after leaving.

| Column     | Type      | Notes                                                         |
|------------|-----------|---------------------------------------------------------------|
| id         | ulid (PK) |                                                               |
| club_id    | ulid (FK) | → clubs.id ON DELETE RESTRICT ON UPDATE CASCADE               |
| player_id  | ulid (FK) | → players.id ON DELETE RESTRICT ON UPDATE CASCADE             |
| started_at | datetime  | When the player joined this club                              |
| ended_at   | datetime  | Nullable — null means the membership is currently active      |
| *audit*    |           | EditorTrait                                                   |

**Indexes**: `(club_id, player_id)` for fast membership lookups.

**Business rule**: A player should not have two active (ended_at IS NULL) memberships at the same club simultaneously. Enforced at the application level.

---

### 7. `tournaments`

The top-level event container. Represents a named competition event (e.g., "Kathmandu Open Judo Championship 2026"). Does **not** store category-specific data — those belong in `tournament_categories`.

> `status` is computed by `TournamentStatusResolver` from category states. Do not mutate it directly outside of the resolver or an explicit cancel action.

| Column      | Type        | Notes                                                      |
|-------------|-------------|------------------------------------------------------------|
| id          | ulid (PK)   |                                                            |
| name        | string      | e.g. "Kathmandu Open Judo Championship 2026"               |
| description | text        | Nullable                                                   |
| logo_url    | string      | Nullable                                                   |
| level       | tinyint     | See [Tournament Level](#tournament-level)                  |
| start_at    | datetime    |                                                            |
| end_at      | datetime    | Nullable                                                   |
| status      | tinyint     | See [Tournament Status](#tournament-status) — computed     |
| venue_id    | ulid (FK)   | → venues.id RESTRICT                                       |
| rule_set_id | ulid (FK)   | → tournament_rule_sets.id RESTRICT. Nullable               |
| timezone    | string(64)  | IANA timezone e.g. "Asia/Kathmandu". Nullable              |
| *audit*     |             | EditorTrait                                                |

**Indexes**: `venue_id`, `status`, `start_at`

**CHECK constraint**: `end_at IS NULL OR start_at <= end_at`


---

### 8. `tournament_categories`

Represents a single competitive division within a tournament (e.g., "Male Cadets -73kg"). Each category runs its own bracket, matches, and produces its own medal results.

A player may register into **multiple categories** within the same tournament.

| Column                 | Type             | Notes                                                            |
|------------------------|------------------|------------------------------------------------------------------|
| id                     | ulid (PK)        |                                                                  |
| tournament_id          | ulid (FK)        | → tournaments.id RESTRICT                                        |
| name                   | string           | Nullable — auto-generated from enum labels if null               |
| gender                 | tinyint          | See [Gender](#gender)                                            |
| age_category           | tinyint          | See [Age Category](#age-category)                                |
| min_age                | tinyint          | Minimum age inclusive (years) — set automatically from enum      |
| max_age                | tinyint          | Maximum age inclusive (years) — set automatically from enum      |
| weight_category        | tinyint          | See [Weight Category](#weight-category)                          |
| min_weight             | decimal(5,2)     | Minimum weight in kg — set automatically from enum, adjustable   |
| max_weight             | decimal(5,2)     | Maximum weight in kg — set automatically from enum, adjustable   |
| status                 | tinyint          | See [Category Status](#category-status)                          |
| registration_open_at   | datetime         | Nullable — when registration opens (null = manual open only)     |
| registration_close_at  | datetime         | Nullable — when registration closes (null = manual close only)   |
| start_at               | datetime         | Nullable — when this category's matches begin                    |
| end_at                 | datetime         | Nullable — when this category finishes                           |
| weigh_in_start_at      | datetime         | Nullable — start of the official weigh-in window                 |
| weigh_in_end_at        | datetime         | Nullable — end of the official weigh-in window                   |
| *audit*                |                  | EditorTrait                                                      |

**Auto-generated name logic** — when `name` is null, the display name is composed from enum labels:
```
{gender_label} {age_category_label} {weight_category_label}
→ "Male Cadets -73kg"
→ "Female Seniors +78kg"
```

**min_age / max_age and min_weight / max_weight**: Populated automatically when the manager selects the enum values. Used for registration eligibility filtering. Managers may adjust them for non-standard events.

**Scheduling columns**: `registration_open_at` / `registration_close_at` drive the automatic status transitions (when a scheduler job is running). The `weigh_in_*` window documents the official weigh-in period. All are nullable — omitting them means status transitions are manual only.

**Indexes**: `tournament_id`, `status`, `registration_close_at`, `start_at`, `weigh_in_start_at`

**Unique constraint**: `(tournament_id, gender, age_category, weight_category, deleted_at)` named `tc_division_unique` — prevents duplicate divisions, soft-delete safe.

**CHECK constraints**:
- `chk_tc_age`: `min_age <= max_age`
- `chk_tc_weight`: `min_weight <= max_weight`
- `chk_tc_registration_window`: `registration_open_at IS NULL OR registration_close_at IS NULL OR registration_open_at <= registration_close_at`
- `chk_tc_schedule`: `start_at IS NULL OR end_at IS NULL OR start_at <= end_at`
- `chk_tc_weigh_in_window`: `weigh_in_start_at IS NULL OR weigh_in_end_at IS NULL OR weigh_in_start_at <= weigh_in_end_at`

> **Note**: `bracket_type` lives in `tournament_brackets`, not here. A category may have multiple bracket structures (e.g., pool stage + knockout final), so the format is defined at the bracket level.

---

### 9. `tournament_registrations`

Records a player's enrollment into a specific competition category. Captures age and weight **at the time of registration** as a snapshot. This record becomes the permanent "competitor" identity referenced throughout brackets and matches.

A player may have multiple registration records — one per category they enter within the same tournament.

| Column                 | Type            | Notes                                                  |
|------------------------|-----------------|--------------------------------------------------------|
| id                     | ulid (PK)       |                                                        |
| tournament_category_id | ulid (FK)       | → tournament_categories.id (restrict)                  |
| club_id                | ulid (FK)       | → clubs.id (restrict)                                  |
| player_id              | ulid (FK)       | → players.id (restrict)                                |
| player_age             | tinyint unsigned| Age at time of registration (max 255, sufficient)      |
| status                 | tinyint         | See [Registration Status](#registration-status)        |
| *audit*                |                 | EditorTrait                                            |

**Constraints**: Unique index on `(tournament_category_id, player_id)` — one registration per player per category.

**FK behaviour**: `tournament_category_id` is `RESTRICT` — a category cannot be deleted while it has registrations. Managers must withdraw all registrations before removing a category.

> `tournament_match_competitors.registration_id` references this table. The registration record must not be deleted once a competitor has been placed in a match.

---

### 10. `tournament_registration_fees`

Registration fee for a specific category. One fee record per category.

| Column                 | Type           | Notes |
|------------------------|----------------|-------|
| id                     | ulid (PK)      | |
| tournament_category_id | ulid (FK)      | → tournament_categories.id RESTRICT |
| amount                 | decimal(10,2)  | Fee amount unsigned |
| currency               | char(3)        | ISO 4217 e.g. NPR, USD |
| *audit*                |                | EditorTrait |

---

### 11. `tournament_registration_payments`

Individual payment records for a registration. A registration may have multiple partial payments.

| Column          | Type           | Notes |
|-----------------|----------------|-------|
| id              | ulid (PK)      | |
| registration_id | ulid (FK)      | → tournament_registrations.id RESTRICT |
| amount_paid     | decimal(10,2)  | |
| currency        | char(3)        | ISO 4217 |
| paid_at         | datetime       | Nullable |
| method          | tinyint        | See [Payment Method](#payment-method) |
| reference       | string         | Nullable — receipt number, bank ref, etc. |
| *audit*         |                | EditorTrait |

**Index**: `registration_id`

---

### 12. `tournament_weigh_ins`

Records each official weigh-in attempt for a registration. The most recent record by `weighed_at` is the official weight.

| Column          | Type           | Notes |
|-----------------|----------------|-------|
| id              | ulid (PK)      | |
| registration_id | ulid (FK)      | → tournament_registrations.id RESTRICT |
| weight          | decimal(5,2)   | Recorded weight in kg |
| made_weight     | boolean        | Computed at record time: weight within category min/max bounds |
| weighed_at      | datetime       | When the weigh-in was recorded |
| weighed_by      | bigint (FK)    | → users.id RESTRICT. Nullable — official who recorded it |
| notes           | string         | Nullable |
| *audit*         |                | EditorTrait |

**Index**: `(registration_id, weighed_at)`

---

### 13. `tournament_registration_coaches`

Associates a coach with a registration (the coach accompanying that player in a category).

| Column          | Type      | Notes |
|-----------------|-----------|-------|
| id              | ulid (PK) | |
| registration_id | ulid (FK) | → tournament_registrations.id RESTRICT |
| coach_id        | ulid (FK) | → coaches.id RESTRICT |
| *audit*         |           | EditorTrait |

**Unique constraint**: `(registration_id, coach_id, deleted_at)` named `trc_registration_coach_unique` — soft-delete safe.

---

### 14. `tournament_brackets`

Represents the competition structure for a single category. One category normally has one bracket. Multiple brackets per category are supported for formats like "Pools + Finals" or "Main + Repechage".

| Column                 | Type      | Notes                                                                          |
|------------------------|-----------|--------------------------------------------------------------------------------|
| id                     | ulid (PK) |                                                                                |
| tournament_category_id | ulid (FK) | → tournament_categories.id RESTRICT                                            |
| type                   | tinyint   | See [Bracket Type](#bracket-type)                                              |
| seeding_method         | tinyint   | See [Seeding Method](#seeding-method)                                          |
| competitor_count       | tinyint   | Nullable — set when bracket is generated                                       |
| round_count            | tinyint   | Nullable — set when bracket is generated                                       |
| status                 | tinyint   | See [Bracket Status](#bracket-status)                                          |
| generated_at           | datetime  | Nullable — timestamp when the bracket structure was generated                  |
| *audit*                |           | EditorTrait                                                                    |

**Index**: `tournament_category_id`

---

### 15. `tournament_pools`

A named pool group within a Pool+Final bracket (e.g., "Pool A", "Pool B").

| Column     | Type       | Notes |
|------------|------------|-------|
| id         | ulid (PK)  | |
| bracket_id | ulid (FK)  | → tournament_brackets.id RESTRICT |
| name       | string(50) | e.g. "Pool A", "Pool B" |
| *audit*    |            | EditorTrait |

---

### 16. `tournament_pool_competitors`

Assigns a registration to a pool and optionally stores their finishing position within the pool.

| Column             | Type      | Notes |
|--------------------|-----------|-------|
| id                 | ulid (PK) | |
| pool_id            | ulid (FK) | → tournament_pools.id RESTRICT |
| registration_id    | ulid (FK) | → tournament_registrations.id RESTRICT |
| finishing_position | tinyint   | Nullable — set after pool stage completes |
| *audit*            |           | EditorTrait |

**Unique constraint**: `(pool_id, registration_id, deleted_at)` named `tpc_pool_registration_unique` — soft-delete safe.

---

### 17. `tournament_matches`

Represents one fight within a bracket. Player participants are stored in `tournament_match_competitors`, not directly here.

| Column                    | Type       | Notes |
|---------------------------|------------|-------|
| id                        | ulid (PK)  | |
| bracket_id                | ulid (FK)  | → tournament_brackets.id RESTRICT |
| pool_id                   | ulid (FK)  | → tournament_pools.id RESTRICT. Nullable — set only for pool-stage matches |
| mat_id                    | ulid (FK)  | → venue_mats.id RESTRICT. Nullable — assigned tatami mat |
| bracket_section           | tinyint    | See [Bracket Section](#bracket-section) |
| round_number              | tinyint    | Numeric round within this section (1 = first round) |
| round_name                | string(50) | Display label: "Round of 16", "Quarterfinal", "Semifinal", "Final" |
| match_number              | tinyint    | Position within the round (1-based) |
| status                    | tinyint    | See [Match Status](#match-status) |
| match_outcome             | tinyint    | Nullable — See [Match Outcome](#match-outcome). Set when completed |
| is_golden_score           | boolean    | Default false — whether result was decided in golden score |
| golden_score_duration_seconds | integer | Nullable — duration of golden score period in seconds |
| winning_competitor_id     | ulid (FK)  | → tournament_match_competitors.id RESTRICT. Null until completed |
| winner_next_match_id      | ulid (FK)  | → tournament_matches.id RESTRICT (self-ref). Where winner advances |
| winner_next_position      | tinyint    | Nullable — 0=White or 1=Blue slot in the next match |
| loser_next_match_id       | ulid (FK)  | → tournament_matches.id RESTRICT (self-ref). For repechage |
| loser_next_position       | tinyint    | Nullable — 0=White or 1=Blue slot in the repechage match |
| scheduled_at              | datetime   | Nullable |
| completed_at              | datetime   | Nullable |
| notes                     | text       | Nullable — referee notes, DQ reason, etc. |
| *audit*                   |            | EditorTrait |

**Indexes**: `(bracket_id, bracket_section, round_number, match_number)` named `tm_bracket_position_index`, `status`, `scheduled_at`, `mat_id`

**CHECK constraints**:
- `chk_tm_winner_pos`: `winner_next_position IS NULL OR winner_next_position IN (0,1)`
- `chk_tm_loser_pos`: `loser_next_position IS NULL OR loser_next_position IN (0,1)`

**Design notes**:
- `winning_competitor_id` → `tournament_match_competitors` (not `players.id`). No denormalised player shortcut.
- `round_name` is stored (not computed) for display flexibility — a manager can override it.
- Self-referential FKs (`winner_next_match_id`, `loser_next_match_id`) are added in a separate migration pass because the table must exist before it can self-reference.

---

### 18. `tournament_match_competitors`

Stores each athlete's per-match record — position (White/Blue), scoring, and result.

| Column          | Type      | Notes                                                                         |
|-----------------|-----------|-------------------------------------------------------------------------------|
| id              | ulid (PK) |                                                                               |
| match_id        | ulid (FK) | → tournament_matches.id RESTRICT                                              |
| registration_id | ulid (FK) | → tournament_registrations.id RESTRICT                                        |
| position        | tinyint   | See [Competitor Position](#competitor-position)                               |
| seed            | tinyint   | Nullable — seeding rank within the bracket                                    |
| score           | tinyint   | Nullable — Waza-ari count. Validated against `rule_set.max_waza_ari`          |
| penalties       | tinyint   | Nullable — Shido count. Validated against `rule_set.max_shido`                |
| result          | tinyint   | Default 0. See [Competitor Result](#competitor-result)                        |
| *audit*         |           | EditorTrait                                                                   |

**Unique constraints** (soft-delete safe):
- `(match_id, position, deleted_at)` named `tmc_match_position_unique`
- `(match_id, registration_id, deleted_at)` named `tmc_match_registration_unique`

**Judo scoring rules enforced at application level**:

| Condition | Action |
|-----------|--------|
| score reaches rule_set.max_waza_ari (default 2) | Automatic win by Ippon — set result = Win |
| penalties reaches rule_set.max_shido (default 3) | Hansoku-make — set result = Disqualified |
| Opponent disqualified | Set own result = Walkover Win |
| No opponent (bye match) | Set result = Walkover Win, score/penalties null |

---

### 19. `tournament_match_referees`

Assigns a referee to a match in a specific role (Center, Side, or Video).

| Column     | Type      | Notes |
|------------|-----------|-------|
| id         | ulid (PK) | |
| match_id   | ulid (FK) | → tournament_matches.id RESTRICT |
| referee_id | ulid (FK) | → referees.id RESTRICT |
| role       | tinyint   | See [Referee Role](#referee-role) |
| *audit*    |           | EditorTrait |

**Unique constraint**: `(match_id, role, deleted_at)` named `tmr_match_role_unique` — one referee per role per match, soft-delete safe.

---

### 20. `tournament_match_events`

Event log for a match. Each scoring event (Ippon, WazaAri, Shido, etc.) is appended here. Scores and penalties on `tournament_match_competitors` are derived from aggregating this log.

| Column              | Type      | Notes |
|---------------------|-----------|-------|
| id                  | ulid (PK) | |
| match_competitor_id | ulid (FK) | → tournament_match_competitors.id RESTRICT |
| event_type          | tinyint   | See [Match Event Type](#match-event-type) |
| elapsed_seconds     | int       | Seconds from match start when the event occurred |
| occurred_at         | datetime  | Wall-clock timestamp of the event |
| notes               | string    | Nullable |
| *audit*             |           | EditorTrait |

**Index**: `(match_competitor_id, elapsed_seconds)` named `tme_competitor_elapsed_index`

---

### 21. `tournament_results`

Stores the final finishing position of every competitor per category. Populated automatically on category completion; managers may override manually.

| Column          | Type      | Notes                                                       |
|-----------------|-----------|-------------------------------------------------------------|
| id              | ulid (PK) |                                                             |
| registration_id | ulid (FK) | → tournament_registrations.id RESTRICT                      |
| position        | tinyint   | Final finishing position — see position conventions below   |
| *audit*         |           | EditorTrait                                                 |

**Unique constraint**: `(registration_id)` — one result record per competitor per category.

**Position conventions**:

| Position | Meaning | Expected rows per category |
|----------|---------|----------------------------|
| 1 | Gold medal | 1 |
| 2 | Silver medal | 1 |
| 3 | Bronze medal | 2 (both semifinal losers) |
| 5 | 5th place | 2 (repechage semifinal losers) |
| 7 | 7th place | 2 (repechage quarter-final losers, if tracked) |

---

## Enumeration Values

All enums are stored as `tinyint unsigned` unless noted otherwise.

---

### Gender

| Value | Label  |
|-------|--------|
| 0     | Male   |
| 1     | Female |

> Used in: `players.gender`, `tournament_categories.gender`

---

### Tournament Level

| Value | Label         |
|-------|---------------|
| 1     | Local         |
| 2     | Regional      |
| 3     | National      |
| 4     | International |

---

### Tournament Status

Computed by `TournamentStatusResolver` from category states. Do not set directly — use the `cancelTournament` action for Cancelled.

| Value | Label      | Resolver Rule |
|-------|------------|---------------|
| 0     | Scheduled  | All non-cancelled categories are pre-bracket (Draft/RegistrationOpen/RegistrationClosed/WeighIn), or none exist |
| 1     | InProgress | At least one category is BracketGenerated or InProgress |
| 2     | Completed  | All non-cancelled categories are Completed |
| 3     | Cancelled  | Manager-set via `cancelTournament` action; resolver never overrides this |

Once Cancelled, no further mutations are allowed on the tournament or any of its nested records.

---

### Category Status

| Value | Label               | Description |
|-------|---------------------|-------------|
| 0     | Draft               | Category created; not yet accepting registrations |
| 1     | RegistrationOpen    | Registrations being accepted |
| 2     | RegistrationClosed  | Registration closed; weigh-in / seeding may begin |
| 3     | WeighIn             | Official weigh-in phase active |
| 4     | BracketGenerated    | Bracket created; matches scheduled; ready to start |
| 5     | InProgress          | Matches being contested |
| 6     | Completed           | All matches done, results finalised (terminal) |
| 7     | Cancelled           | Category cancelled (terminal) |

---

### Age Category

Standard Judo age divisions. `min_age` and `max_age` are populated automatically from these defaults but may be adjusted by managers.

| Value | Label          | Default Age Range | IJF Equivalent |
|-------|----------------|-------------------|----------------|
| 0     | Preparation    | 1–6               | —              |
| 1     | Super Mini     | 7–8               | —              |
| 2     | Mini           | 9–10              | —              |
| 3     | Pupils         | 11–12             | —              |
| 4     | Juveniles      | 13–14             | —              |
| 5     | Cadets         | 15–17             | U18            |
| 6     | Juniors        | 18–20             | U21            |
| 7     | Under 23       | 15–22             | U23            |
| 8     | Seniors        | 21–30             | Senior         |
| 9     | Veterans       | 31–99             | Veteran        |

> Used in: `tournament_categories.age_category`

---

### Weight Category

Standard IJF weight classes. `min_weight` and `max_weight` are populated automatically from these defaults but may be adjusted by managers.

| Value | Label    | Gender      | Default Range (kg) |
|-------|----------|-------------|--------------------|
| 0     | -48 kg   | Female      | 0–48               |
| 1     | -52 kg   | Female      | 49–52              |
| 2     | -57 kg   | Female      | 53–57              |
| 3     | -60 kg   | Male        | 0–60               |
| 4     | -63 kg   | Female      | 58–63              |
| 5     | -66 kg   | Male        | 61–66              |
| 6     | -70 kg   | Female      | 64–70              |
| 7     | -73 kg   | Male        | 67–73              |
| 8     | -78 kg   | Female      | 71–78              |
| 9     | -81 kg   | Male        | 74–81              |
| 10    | -90 kg   | Male        | 82–90              |
| 11    | -100 kg  | Male        | 91–100             |
| 12    | +100 kg  | Male        | 101–999            |
| 13    | +78 kg   | Female      | 79–999             |

> Used in: `tournament_categories.weight_category`
> The "Gender" column is a reference guide only — it is not enforced at DB level. The `tournament_categories.gender` field controls which athletes are eligible.

---

### Registration Status

| Value | Label      | Description                                                       |
|-------|------------|-------------------------------------------------------------------|
| 0     | Registered | Default — player has been registered into the category            |
| 1     | Confirmed  | Registration locked — bracket generation is imminent             |
| 2     | Withdrawn  | Player has withdrawn. Record kept for history, excluded from bracket |

> Used in: `tournament_registrations.status`
> A registration with status `Withdrawn` is excluded from bracket seeding. Only `Registered` and `Confirmed` athletes are included.

---

### Seeding Method

| Value | Label     | Description                                              |
|-------|-----------|----------------------------------------------------------|
| 0     | Random    | Players are randomly assigned to bracket positions       |
| 1     | Manual    | Manager assigns seeds manually before generation         |
| 2     | By Rank   | Seeds assigned by player ranking or registration order   |

> Used in: `tournament_brackets.seeding_method`

---

### Bracket Section

Which part of a bracket structure a match belongs to.

| Value | Label      | Description                                              |
|-------|------------|----------------------------------------------------------|
| 0     | Main       | Standard knockout matches                                |
| 1     | Repechage  | Second-chance bracket for athletes who lost to finalists |
| 2     | Bronze     | Bronze medal fight (repechage final)                     |
| 3     | GoldFinal  | Gold medal match                                         |

> Used in: `tournament_matches.bracket_section`

---

### Bracket Type

| Value | Label              | Description                                          |
|-------|--------------------|------------------------------------------------------|
| 0     | Single Elimination | Standard knockout — loss = eliminated                |
| 1     | Double Elimination | Two losses required for elimination                  |
| 2     | Pool + Final       | Round-robin pools, then knockout final stage         |
| 3     | Repechage (IJF)    | Standard international — losers to finalists fight for Bronze |

> Used in: `tournament_brackets.type`

---

### Bracket Status

| Value | Label     | Description                                    |
|-------|-----------|------------------------------------------------|
| 0     | Pending   | Bracket created, not yet generated             |
| 1     | Active    | Matches in progress                            |
| 2     | Completed | All matches in this bracket are done           |

---

### Match Status

| Value | Label     |
|-------|-----------|
| 0     | Pending   |
| 1     | Active    |
| 2     | Completed |

---

### Competitor Position

Position of the athlete within a match. Corresponds to judogi colour in Judo.

| Value | Label | Description              |
|-------|-------|--------------------------|
| 0     | White | Shiro — first competitor |
| 1     | Blue  | Ao — second competitor   |

---

### Competitor Result

| Value | Label         | Description                                       |
|-------|---------------|---------------------------------------------------|
| 0     | Pending       | Match not yet completed                           |
| 1     | Win           | Athlete won the match                             |
| 2     | Loss          | Athlete lost the match                            |
| 3     | WalkoverWin   | Opponent withdrew or did not appear — auto-win    |
| 4     | Disqualified  | Hansoku-make — three Shido or grave violation     |

---

### Match Outcome

How the match was decided. Set when the match completes.

| Value | Label         | Description |
|-------|---------------|-------------|
| 0     | Ippon         | Full point — automatic win |
| 1     | WazaAri       | Two waza-ari = ippon |
| 2     | Decision      | Judged decision (flag vote) |
| 3     | HansokuMake   | Disqualification |
| 4     | Walkover      | Opponent withdrew or did not appear |
| 5     | Bye           | No opponent (bracket padding) |
| 6     | GoldenScore   | Decided in golden score overtime |

> Used in: `tournament_matches.match_outcome`

---

### Match Event Type

Scoring and penalty events recorded during a match.

| Value | Label             | Description |
|-------|-------------------|-------------|
| 0     | Ippon             | Full point scored |
| 1     | WazaAri           | Half point scored |
| 2     | Shido             | Minor penalty |
| 3     | HansokuMake       | Disqualification (grave violation or 3rd Shido) |
| 4     | HansokuMakeLight  | Indirect hansoku-make (awarded to opponent as ippon) |
| 5     | Kinsa             | Slight advantage (older scoring; kept for historical records) |
| 6     | Note              | Referee note (no scoring effect) |

> Used in: `tournament_match_events.event_type`

---

### Referee Role

| Value | Label  | Description |
|-------|--------|-------------|
| 0     | Center | Main referee on the mat |
| 1     | Side   | Corner judge |
| 2     | Video  | Video review official |

> Used in: `tournament_match_referees.role`

---

### Payment Method

| Value | Label  |
|-------|--------|
| 0     | Cash   |
| 1     | Bank   |
| 2     | Online |

> Used in: `tournament_registration_payments.method`

---

## Migrations

All 27 domain migrations have been implemented and verified with `php artisan migrate:fresh`.

| # | Migration File | Status |
|---|----------------|--------|
| 1 | `0001_01_01_000000_create_users_table.php` | ✓ |
| 2 | `2026_01_01_000001_create_addresses_table.php` | ✓ |
| 3 | `2026_01_01_000002_create_venues_table.php` | ✓ |
| 4 | `2026_01_01_000003_create_tournament_rule_sets_table.php` | ✓ |
| 5 | `2026_01_01_000004_create_countries_table.php` | ✓ |
| 6 | `2026_01_01_000005_create_belt_ranks_table.php` | ✓ |
| 7 | `2026_01_01_000006_create_venue_mats_table.php` | ✓ |
| 8 | `2026_01_01_000007_create_clubs_table.php` | ✓ |
| 9 | `2026_01_01_000008_create_players_table.php` | ✓ |
| 10 | `2026_01_01_000009_create_clubs_players_table.php` | ✓ |
| 11 | `2026_01_01_000010_create_referees_table.php` | ✓ |
| 12 | `2026_01_01_000011_create_coaches_table.php` | ✓ |
| 13 | `2026_01_01_000012_create_tournaments_table.php` | ✓ |
| 14 | `2026_01_01_000013_create_tournament_categories_table.php` | ✓ |
| 15 | `2026_01_01_000014_create_tournament_registrations_table.php` | ✓ |
| 16 | `2026_01_01_000015_create_tournament_registration_fees_table.php` | ✓ |
| 17 | `2026_01_01_000016_create_tournament_registration_payments_table.php` | ✓ |
| 18 | `2026_01_01_000017_create_tournament_weigh_ins_table.php` | ✓ |
| 19 | `2026_01_01_000018_create_tournament_registration_coaches_table.php` | ✓ |
| 20 | `2026_01_01_000019_create_tournament_brackets_table.php` | ✓ |
| 21 | `2026_01_01_000020_create_tournament_pools_table.php` | ✓ |
| 22 | `2026_01_01_000021_create_tournament_pool_competitors_table.php` | ✓ |
| 23 | `2026_01_01_000022_create_tournament_matches_table.php` | ✓ |
| 24 | `2026_01_01_000023_create_tournament_match_competitors_table.php` | ✓ |
| 25 | `2026_01_01_000024_create_tournament_match_referees_table.php` | ✓ |
| 26 | `2026_01_01_000025_create_tournament_match_events_table.php` | ✓ |
| 27 | `2026_01_01_000026_create_tournament_results_table.php` | ✓ |
| +  | `2026_01_01_000027_add_tournament_matches_self_ref_fks.php` | ✓ — adds winning_competitor_id, winner_next_match_id, loser_next_match_id in a second pass |
| +  | `remove_player_weight_from_tournament_registrations_table.php` | ⏳ pending — drops player_weight; official weight is in tournament_weigh_ins |

---

## Complete Schema Diagram

```
tournament_rule_sets
  id (ulid PK)
  name, max_waza_ari, max_shido, match_duration_seconds, golden_score_seconds

venue_mats
  id (ulid PK)
  venue_id → venues.id (restrict)
  mat_number (tinyint), display_name

venues
  id (ulid PK)
  name
  address_id → addresses.id (restrict)

clubs
  id (ulid PK)
  name, abbreviation, logo_url
  address_id → addresses.id (set null, nullable)
  phone_number

players
  id (ulid PK)
  name, gender, dob, weight (decimal)
  nationality_code → countries.code
  current_belt_rank_id → belt_ranks.id (nullable)
  email, profile_photo_url, phone_number

clubs_players
  id (ulid PK)
  club_id    → clubs.id (restrict)
  player_id  → players.id (restrict)
  belt_rank_id → belt_ranks.id (nullable)
  grade_certificate, started_at, ended_at
  INDEX (club_id, player_id)

referees
  id (ulid PK)
  user_id → users.id (nullable)
  full_name, nationality_code, certification_level

coaches
  id (ulid PK)
  user_id → users.id (nullable)
  full_name
  club_id → clubs.id (nullable, restrict)

tournaments
  id (ulid PK)
  venue_id     → venues.id (restrict)
  rule_set_id  → tournament_rule_sets.id (restrict, nullable)
  name, description, logo_url
  level, status (computed by TournamentStatusResolver)
  timezone (IANA string, nullable)
  start_at, end_at
  CHECK (end_at IS NULL OR start_at <= end_at)

tournament_categories
  id (ulid PK)
  tournament_id → tournaments.id (restrict)
  name (nullable — auto-generated)
  gender, age_category, min_age, max_age
  weight_category, min_weight (decimal), max_weight (decimal)
  status (0=Draft … 7=Cancelled)
  registration_open_at, registration_close_at   ← scheduling (nullable)
  weigh_in_start_at, weigh_in_end_at            ← weigh-in window (nullable)
  start_at, end_at                              ← category schedule (nullable)
  UNIQUE (tournament_id, gender, age_category, weight_category, deleted_at)
  CHECK constraints: age, weight, registration window, weigh-in window, schedule

tournament_registrations
  id (ulid PK)
  tournament_category_id → tournament_categories.id (restrict)
  club_id                → clubs.id (restrict)
  player_id              → players.id (restrict)
  player_age (tinyint)   ← snapshot at registration time; official weight is in tournament_weigh_ins
  status  (0=Registered / 1=Confirmed / 2=Withdrawn)
  UNIQUE (tournament_category_id, player_id, deleted_at)

tournament_registration_fees
  id (ulid PK)
  tournament_category_id → tournament_categories.id (restrict)
  amount (decimal), currency (char 3)

tournament_registration_payments
  id (ulid PK)
  registration_id → tournament_registrations.id (restrict)
  amount_paid (decimal), currency, paid_at, method, reference

tournament_weigh_ins
  id (ulid PK)
  registration_id → tournament_registrations.id (restrict)
  weight (decimal), made_weight (bool), weighed_at, weighed_by → users.id
  INDEX (registration_id, weighed_at)

tournament_registration_coaches
  id (ulid PK)
  registration_id → tournament_registrations.id (restrict)
  coach_id        → coaches.id (restrict)
  UNIQUE (registration_id, coach_id, deleted_at)

tournament_brackets
  id (ulid PK)
  tournament_category_id → tournament_categories.id (restrict)
  type (0=SingleElim / 1=DoubleElim / 2=PoolFinal / 3=Repechage)
  seeding_method (0=Random / 1=Manual / 2=ByRank)
  competitor_count, round_count (nullable — set on generation)
  status (0=Pending / 1=Active / 2=Completed), generated_at

tournament_pools
  id (ulid PK)
  bracket_id → tournament_brackets.id (restrict)
  name (string 50)

tournament_pool_competitors
  id (ulid PK)
  pool_id         → tournament_pools.id (restrict)
  registration_id → tournament_registrations.id (restrict)
  finishing_position (nullable)
  UNIQUE (pool_id, registration_id, deleted_at)

tournament_matches
  id (ulid PK)
  bracket_id             → tournament_brackets.id (restrict)
  pool_id                → tournament_pools.id (restrict, nullable)
  mat_id                 → venue_mats.id (restrict, nullable)
  bracket_section        (0=Main / 1=Repechage / 2=Bronze / 3=GoldFinal)
  round_number, round_name, match_number
  status (0=Pending / 1=Active / 2=Completed)
  match_outcome          (nullable — set on completion)
  is_golden_score (bool), golden_score_duration_seconds (nullable)
  winning_competitor_id  → tournament_match_competitors.id (restrict, nullable)
  winner_next_match_id   → tournament_matches.id (restrict, self-ref, nullable)
  winner_next_position   (0=White / 1=Blue, nullable)
  loser_next_match_id    → tournament_matches.id (restrict, self-ref, nullable)
  loser_next_position    (0=White / 1=Blue, nullable)
  scheduled_at, completed_at, notes
  INDEX (bracket_id, bracket_section, round_number, match_number)
  CHECK: winner_next_position IN (0,1), loser_next_position IN (0,1)

tournament_match_competitors
  id (ulid PK)
  match_id        → tournament_matches.id (restrict)
  registration_id → tournament_registrations.id (restrict)
  position        (0=White / 1=Blue)
  seed (nullable), score (nullable), penalties (nullable)
  result (0=Pending / 1=Win / 2=Loss / 3=WalkoverWin / 4=Disqualified)
  UNIQUE (match_id, position, deleted_at)
  UNIQUE (match_id, registration_id, deleted_at)

tournament_match_referees
  id (ulid PK)
  match_id   → tournament_matches.id (restrict)
  referee_id → referees.id (restrict)
  role (0=Center / 1=Side / 2=Video)
  UNIQUE (match_id, role, deleted_at)

tournament_match_events
  id (ulid PK)
  match_competitor_id → tournament_match_competitors.id (restrict)
  event_type (0=Ippon … 6=Note), elapsed_seconds, occurred_at, notes
  INDEX (match_competitor_id, elapsed_seconds)

tournament_results
  id (ulid PK)
  registration_id → tournament_registrations.id (restrict)
  position (1=Gold, 2=Silver, 3=Bronze, 5=5th, 7=7th …)
  UNIQUE (registration_id)
```
