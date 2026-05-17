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
   - [tournament_brackets](#10-tournament_brackets)
   - [tournament_matches](#11-tournament_matches)
   - [tournament_match_competitors](#12-tournament_match_competitors)
   - [tournament_results](#13-tournament_results)
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

| Column      | Type      | Notes                                        |
|-------------|-----------|----------------------------------------------|
| id          | ulid (PK) |                                              |
| name        | string    | e.g. "Kathmandu Open Judo Championship 2026" |
| description | text      | Nullable                                     |
| logo_url    | string    | Nullable                                     |
| level       | tinyint   | See [Tournament Level](#tournament-level)    |
| start_at    | datetime  |                                              |
| end_at      | datetime  | Nullable                                     |
| status      | tinyint   | See [Tournament Status](#tournament-status)  |
| venue_id    | ulid (FK) | → venues.id (restrict)                       |
| *audit*     |           | EditorTrait                                  |


---

### 8. `tournament_categories`

Represents a single competitive division within a tournament (e.g., "Male Cadets -73kg"). Each category runs its own bracket, matches, and produces its own medal results.

A player may register into **multiple categories** within the same tournament.

| Column          | Type      | Notes                                                        |
|-----------------|-----------|--------------------------------------------------------------|
| id              | ulid (PK) |                                                              |
| tournament_id   | ulid (FK) | → tournaments.id (cascade delete)                            |
| name            | string    | auto-generated at application level if null                  |
| gender          | tinyint   | See [Gender](#gender)                                        |
| age_category    | tinyint   | See [Age Category](#age-category)                            |
| min_age         | tinyint   | Minimum age inclusive (years) — set automatically from enum  |
| max_age         | tinyint   | Maximum age inclusive (years) — set automatically from enum  |
| weight_category | tinyint   | See [Weight Category](#weight-category)                      |
| min_weight      | int       | Minimum weight in kg inclusive — set automatically from enum |
| max_weight      | int       | Maximum weight in kg inclusive — set automatically from enum |
| status          | tinyint   | See [Category Status](#category-status)                      |
| *audit*         |           | EditorTrait                                                  |

**Auto-generated name logic** — when `name` is null, the display name is composed from enum labels:
```
{gender_label} {age_category_label} {weight_category_label}
→ "Male Cadets -73kg"
→ "Female Seniors +78kg"
```

**min_age / max_age and min_weight / max_weight**: These are populated automatically by the application when the manager selects the `age_category` and `weight_category` enums. They serve as the actual range values used for registration filtering. Managers may adjust them manually for non-standard events.

**Indexes**: `(tournament_id)`

**Unique constraint**: `(tournament_id, gender, age_category, weight_category)` named `tc_division_unique` — prevents duplicate divisions within the same tournament.

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
| player_weight          | decimal(5,2)    | Weight in kg at time of registration                   |
| status                 | tinyint         | See [Registration Status](#registration-status)        |
| *audit*                |                 | EditorTrait                                            |

**Constraints**: Unique index on `(tournament_category_id, player_id)` — one registration per player per category.

**FK behaviour**: `tournament_category_id` is `RESTRICT` — a category cannot be deleted while it has registrations. Managers must withdraw all registrations before removing a category.

> `tournament_match_competitors.registration_id` references this table. The registration record must not be deleted once a competitor has been placed in a match.

---

### 10. `tournament_brackets`

Represents the competition structure for a single category. One category normally has one bracket. Multiple brackets per category are supported for formats like "Pools + Finals" or "Main + Repechage".

| Column                 | Type      | Notes                                                                          |
|------------------------|-----------|--------------------------------------------------------------------------------|
| id                     | ulid (PK) |                                                                                |
| tournament_category_id | ulid (FK) | → tournament_categories.id (cascade delete)                                    |
| type                   | tinyint   | See [Bracket Type](#bracket-type)                                              |
| seeding_method         | tinyint   | See [Seeding Method](#seeding-method)                                          |
| competitor_count       | tinyint   | Nullable — set when bracket is generated. Number of athletes seeded in         |
| round_count            | tinyint   | Nullable — set when bracket is generated. Total rounds in this bracket         |
| status                 | tinyint   | See [Bracket Status](#bracket-status)                                          |
| generated_at           | datetime  | Nullable — timestamp when the bracket structure was generated                  |
| *audit*                |           | EditorTrait                                                                    |

**Indexes**: `(tournament_category_id)`

> `competitor_count` and `round_count` are null before generation and populated by the BracketService when matches are created.

---

### 11. `tournament_matches`

Represents one fight within a bracket. Matches are linked to a bracket (not directly to a category). The bracket's structure and athlete seeding link back to the category via `tournament_brackets`.

Player participants are **not** stored directly on this table. They are stored in `tournament_match_competitors`.

| Column               | Type      | Notes                                                              |
|----------------------|-----------|--------------------------------------------------------------------|
| id                   | ulid (PK) |                                                                    |
| bracket_id           | ulid (FK) | → tournament_brackets.id (cascade delete)                          |
| bracket_section      | tinyint   | See [Bracket Section](#bracket-section)                            |
| round_number         | tinyint   | Numeric round within this section (1 = first round)               |
| round_name           | string(50)| Display label: "Round of 16", "Quarterfinal", "Semifinal", "Final" |
| match_number         | tinyint   | Position within the round (1-based)                               |
| mat_number           | tinyint   | Nullable — tatami mat assignment for scheduling                    |
| scheduled_at         | datetime  | Nullable                                                           |
| completed_at         | datetime  | Nullable                                                           |
| status               | tinyint   | See [Match Status](#match-status)                                  |
| winner_id            | ulid (FK) | → players.id (set null). Null until match is completed             |
| winner_next_match_id | ulid (FK) | → tournament_matches.id (set null). Where the winner advances      |
| winner_next_position | tinyint   | Nullable — 1 or 2, which slot the winner fills in the next match   |
| loser_next_match_id  | ulid (FK) | → tournament_matches.id (set null). For repechage / double elim    |
| loser_next_position  | tinyint   | Nullable — 1 or 2, which slot the loser fills in the next match    |
| notes                | text      | Nullable — referee notes, DQ reason, golden score outcome, etc.    |
| *audit*              |           | EditorTrait                                                        |

**Indexes**: `(bracket_id, bracket_section, round_number, match_number)`

**Design notes**:
- Athlete participants are stored in `tournament_match_competitors`, not here. This table only holds the structural position of a match in the bracket tree.
- `winner_id` is a denormalised shortcut to `players.id` for fast lookup. The full competitor record (score, penalties, result) is in `tournament_match_competitors`.
- `round_name` is stored (not computed) for display flexibility — a manager can override the label (e.g., "Pool A Round 1").

---

### 12. `tournament_match_competitors`

Stores each athlete's per-match record — their position (Blue/White), scoring, and result. Every match has exactly two rows in this table.

| Column          | Type      | Notes                                                           |
|-----------------|-----------|-----------------------------------------------------------------|
| id              | ulid (PK) |                                                                 |
| match_id        | ulid (FK) | → tournament_matches.id (cascade delete)                        |
| registration_id | ulid (FK) | → tournament_registrations.id (restrict)                        |
| position        | tinyint   | See [Competitor Position](#competitor-position)                 |
| seed            | tinyint   | Nullable — seeding rank within the bracket (max 64 in practice) |
| score           | tinyint   | Nullable — Waza-ari count (0, 1, or 2)                          |
| penalties       | tinyint   | Nullable — Shido count (0, 1, 2, or 3)                          |
| result          | tinyint   | Default 0. See [Competitor Result](#competitor-result)          |
| *audit*         |           | EditorTrait                                                     |

**Constraints**:
- Unique index on `(match_id, position)` — exactly one White and one Blue per match
- Unique index on `(match_id, registration_id)` — a player appears at most once per match

**Judo scoring rules enforced at application level**:

| Condition                   | Action                                      |
|-----------------------------|---------------------------------------------|
| score reaches 2             | Automatic win by Ippon — set result = Win   |
| penalties reaches 3         | Hansoku-make — set result = Disqualified    |
| Opponent disqualified       | Set own result = Walkover Win               |
| No opponent (bye match)     | Set result = Walkover Win, score/penalties null |

---

### 13. `tournament_results`

Stores the final finishing position of every competitor per category. Populated after a category's bracket is complete. Allows manual override by tournament managers.

Using `registration_id` instead of separate `tournament_category_id + player_id` FKs — the registration already implies both the player and the category.

| Column          | Type      | Notes                                                                    |
|-----------------|-----------|--------------------------------------------------------------------------|
| id              | ulid (PK) |                                                                          |
| registration_id | ulid (FK) | → tournament_registrations.id (restrict)                                 |
| position        | tinyint   | Final finishing position. See position conventions below                 |
| *audit*         |           | EditorTrait                                                              |

**Constraints**: Unique index on `(registration_id)` — one result record per competitor per category.

**Position conventions**:

| Position | Meaning            | Expected rows per category |
|----------|--------------------|----------------------------|
| 1        | Gold medal         | 1                          |
| 2        | Silver medal       | 1                          |
| 3        | Bronze medal       | 2 (both semifinal losers)  |
| 5        | 5th place          | 2 (repechage semifinal losers) |
| 7        | 7th place          | 2 (repechage quarter-final losers, if tracked) |

> Position is a plain `tinyint` integer — no separate enum needed. The application controls which positions are created based on the bracket type and depth.

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

| Value | Label       |
|-------|-------------|
| 0     | Scheduled   |
| 1     | In Progress |
| 2     | Completed   |
| 3     | Cancelled   |

---

### Category Status

| Value | Label     | Description                                           |
|-------|-----------|-------------------------------------------------------|
| 0     | Pending   | Category created, registrations open                  |
| 1     | Seeding   | Registrations closed, bracket generation in progress  |
| 2     | Active    | Matches in progress                                   |
| 3     | Completed | All matches done, results recorded                    |

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
| 3     | Final      | Gold medal match                                         |

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
| 3     | Walkover Win  | Opponent withdrew or did not appear — auto-win    |
| 4     | Disqualified  | Hansoku-make — three Shido or grave violation     |

---

## Migrations

All 13 domain migrations have been implemented and verified with `php artisan migrate:fresh`.

| # | Migration File | Status |
|---|----------------|--------|
| 1 | `0001_01_01_000000_create_users_table.php` | ✓ |
| 2 | `2025_01_27_000000_create_addresses_table.php` | ✓ |
| 3 | `2025_01_28_164539_create_venues_table.php` | ✓ |
| 4 | `2025_02_06_164624_create_players_table.php` | ✓ |
| 5 | `2025_02_07_170029_create_clubs_table.php` | ✓ |
| 6 | `2025_02_07_170318_create_clubs_players_table.php` | ✓ |
| 7 | `2025_02_15_095943_create_tournaments_table.php` | ✓ |
| 8 | `2025_02_16_000000_create_tournament_categories_table.php` | ✓ |
| 9 | `2025_05_30_150439_create_tournament_registrations_table.php` | ✓ |
| 10 | `2025_12_24_072100_create_tournament_brackets_table.php` | ✓ |
| 11 | `2025_12_24_072131_create_tournament_matches_table.php` | ✓ |
| 12 | `2025_12_24_072200_create_tournament_match_competitors_table.php` | ✓ |
| 13 | `2025_12_24_072300_create_tournament_results_table.php` | ✓ |

> The file `2025_12_24_072027_add_type_to_tournaments_table.php` was deleted — its intent was merged into `create_tournaments_table` directly.

---

## Complete Schema Diagram

```
users
  id (bigint PK)
  name, email, password
  created_by → users.id (nullable)
  updated_by → users.id (nullable)
  deleted_by → users.id (nullable)
  deleted_at, created_at, updated_at

addresses
  id (ulid PK)
  address, city, state, country

venues
  id (ulid PK)
  name
  address_id → addresses.id (restrict)
  mat_count

clubs
  id (ulid PK)
  name, abbreviation, logo_url
  address_id → addresses.id (set null, nullable)
  phone_number

players
  id (ulid PK)
  name, gender, dob, weight
  nationality, email
  profile_photo_url, phone_number

clubs_players
  id (ulid PK)
  club_id    → clubs.id (restrict)
  player_id  → players.id (restrict)
  started_at, ended_at
  INDEX (club_id, player_id)

tournaments
  id (ulid PK)
  venue_id   → venues.id
  name, description, logo_url
  level, status
  start_at, end_at

tournament_categories
  id (ulid PK)
  tournament_id → tournaments.id
  name (nullable — auto-generated from enum labels)
  gender (tinyint enum)
  age_category (tinyint enum), min_age, max_age
  weight_category (tinyint enum), min_weight, max_weight
  status
  UNIQUE (tournament_id, gender, age_category, weight_category)

tournament_registrations
  id (ulid PK)
  tournament_category_id → tournament_categories.id (restrict)
  club_id                → clubs.id (restrict)
  player_id              → players.id (restrict)
  player_age (tinyint), player_weight  ← snapshot at registration time
  status  (0=Registered / 1=Confirmed / 2=Withdrawn)
  UNIQUE (tournament_category_id, player_id)

tournament_brackets
  id (ulid PK)
  tournament_category_id → tournament_categories.id (cascade)
  type            (0=Single Elim / 1=Double Elim / 2=Pool+Final / 3=Repechage)
  seeding_method  (0=Random / 1=Manual / 2=By Rank)
  competitor_count (tinyint, nullable — set on generation)
  round_count      (tinyint, nullable — set on generation)
  status, generated_at

tournament_matches
  id (ulid PK)
  bracket_id            → tournament_brackets.id (cascade)
  bracket_section       (0=Main / 1=Repechage / 2=Bronze / 3=Final)
  round_number (tinyint), round_name, match_number (tinyint)
  mat_number (tinyint, nullable)
  scheduled_at, completed_at, status
  winner_id             → players.id (set null, nullable)
  winner_next_match_id  → tournament_matches.id (set null, self-ref)
  winner_next_position  (1 or 2)
  loser_next_match_id   → tournament_matches.id (set null, self-ref)
  loser_next_position   (1 or 2)
  notes
  INDEX (bracket_id, bracket_section, round_number, match_number)

tournament_match_competitors
  id (ulid PK)
  match_id        → tournament_matches.id (cascade)
  registration_id → tournament_registrations.id (restrict)
  position        (0=White / 1=Blue)
  seed            (tinyint, nullable)
  score           (tinyint, nullable — Waza-ari count)
  penalties       (tinyint, nullable — Shido count)
  result          (default 0: Pending / 1=Win / 2=Loss / 3=Walkover / 4=Disqualified)
  UNIQUE (match_id, position)
  UNIQUE (match_id, registration_id)

tournament_results
  id (ulid PK)
  registration_id → tournament_registrations.id (restrict)
  position        (tinyint: 1=Gold, 2=Silver, 3=Bronze, 5=5th, 7=7th ...)
  UNIQUE (registration_id)
```
