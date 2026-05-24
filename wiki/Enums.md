# Enums

All backend enums are PHP 8 int-backed enums under `app/System/{Entity}/Foundation/Enums/`. Stored as `tinyint unsigned` in the database. Mirrored as TypeScript enums on the frontend.

> Canonical source: enum files in `app/System/.../Foundation/Enums/`. This page is a quick reference — when in doubt, read the source.

---

## Player

### `PlayerGenderEnum` — `app/System/Player/Foundation/Enums/`

| Value | Case   |
|-------|--------|
| 0     | Male   |
| 1     | Female |

Used in `players.gender`, `tournament_categories.gender`.

---

## Club / Membership

### `BeltRankEnum` — `app/System/ClubPlayer/Foundation/Enums/`

| Value | Case   |
|-------|--------|
| 0     | White  |
| 1     | Yellow |
| 2     | Orange |
| 3     | Green  |
| 4     | Blue   |
| 5     | Purple |
| 6     | Brown  |
| 7     | Black  |

Stored on `clubs_players.belt_rank` (nullable).

---

## Tournament

All tournament enums live in `app/System/Tournament/Foundation/Enums/`.

### `TournamentLevelEnum`

| Value | Case          |
|-------|---------------|
| 1     | Local         |
| 2     | Regional      |
| 3     | National      |
| 4     | International |

Used in `tournaments.level`.

### `TournamentStatusEnum`

Computed by `TournamentStatusResolver`. Do not set directly (except via the `cancelTournament` action).

| Value | Case        |
|-------|-------------|
| 0     | Scheduled   |
| 1     | InProgress  |
| 2     | Completed   |
| 3     | Cancelled   |

Used in `tournaments.status`. Once Cancelled, no further actions may be taken on the tournament or its categories.

### `CategoryStatusEnum`

| Value | Case               | Meaning |
|-------|--------------------|---------|
| 0     | Draft              | Created; not yet accepting registrations |
| 1     | RegistrationOpen   | Registrations being accepted |
| 2     | RegistrationClosed | Registration closed; weigh-in / seeding may begin |
| 3     | WeighIn            | Official weigh-in phase active |
| 4     | BracketGenerated   | Bracket created; matches scheduled; ready to start |
| 5     | InProgress         | Matches being contested |
| 6     | Completed          | All matches done, results finalised (terminal) |
| 7     | Cancelled          | Category cancelled (terminal) |

Used in `tournament_categories.status`. Status is never set directly — always transition through `CategoryStatusTransitionService`.

### `RegistrationStatusEnum`

| Value | Case       | Meaning |
|-------|------------|---------|
| 0     | Registered | Default — player enrolled |
| 1     | Confirmed  | Locked, bracket generation imminent |
| 2     | Withdrawn  | Excluded from bracket but kept for history |

Used in `tournament_registrations.status`.

### `AgeCategoryEnum`

Default age ranges; managers may adjust `min_age`/`max_age` manually.

| Value | Case        | Range  | IJF Equivalent |
|-------|-------------|--------|----------------|
| 0     | Preparation | 1–6    | — |
| 1     | SuperMini   | 7–8    | — |
| 2     | Mini        | 9–10   | — |
| 3     | Pupils      | 11–12  | — |
| 4     | Juveniles   | 13–14  | — |
| 5     | Cadets      | 15–17  | U18 |
| 6     | Juniors     | 18–20  | U21 |
| 7     | Under23     | 15–22  | U23 |
| 8     | Seniors     | 21–30  | Senior |
| 9     | Veterans    | 31–99  | Veteran |

Used in `tournament_categories.age_category`.

### `WeightCategoryEnum`

Default weight ranges. `min_weight`/`max_weight` may be adjusted per category. The Gender column is for reference only — DB-level gender comes from `tournament_categories.gender`.

| Value | Case           | Gender | Range (kg) |
|-------|----------------|--------|------------|
| 0     | Female48       | F | 0–48 |
| 1     | Female52       | F | 49–52 |
| 2     | Female57       | F | 53–57 |
| 3     | Male60         | M | 0–60 |
| 4     | Female63       | F | 58–63 |
| 5     | Male66         | M | 61–66 |
| 6     | Female70       | F | 64–70 |
| 7     | Male73         | M | 67–73 |
| 8     | Female78       | F | 71–78 |
| 9     | Male81         | M | 74–81 |
| 10    | Male90         | M | 82–90 |
| 11    | Male100        | M | 91–100 |
| 12    | Male100Plus    | M | 101+ |
| 13    | Female78Plus   | F | 79+ |

Used in `tournament_categories.weight_category`.

### `BracketTypeEnum`

| Value | Case               | Meaning |
|-------|--------------------|---------|
| 0     | SingleElimination  | Standard knockout — loss = eliminated |
| 1     | DoubleElimination  | Two losses required for elimination |
| 2     | PoolFinal          | Round-robin pools + knockout finals |
| 3     | Repechage          | IJF standard — losers to finalists fight for bronze |

Used in `tournament_brackets.type`.

### `BracketStatusEnum`

| Value | Case      |
|-------|-----------|
| 0     | Pending   |
| 1     | Active    |
| 2     | Completed |

Used in `tournament_brackets.status`.

### `BracketSectionEnum`

| Value | Case       | Meaning |
|-------|------------|---------|
| 0     | Main       | Standard knockout matches |
| 1     | Repechage  | Second-chance bracket |
| 2     | Bronze     | Bronze medal fight |
| 3     | GoldFinal  | Gold medal match |

Used in `tournament_matches.bracket_section`.

### `SeedingMethodEnum`

| Value | Case   | Meaning |
|-------|--------|---------|
| 0     | Random | Players are randomly assigned to bracket positions |
| 1     | Manual | Manager assigns seeds manually |
| 2     | ByRank | By player ranking or registration order |

Used in `tournament_brackets.seeding_method`.

### `MatchStatusEnum`

| Value | Case      |
|-------|-----------|
| 0     | Pending   |
| 1     | Active    |
| 2     | Completed |

Used in `tournament_matches.status`.

### `CompetitorPositionEnum`

| Value | Case  | Meaning |
|-------|-------|---------|
| 0     | White | Shiro — first competitor |
| 1     | Blue  | Ao — second competitor |

Used in `tournament_match_competitors.position`.

### `CompetitorResultEnum`

| Value | Case          | Meaning |
|-------|---------------|---------|
| 0     | Pending       | Match not yet completed |
| 1     | Win           | Athlete won |
| 2     | Loss          | Athlete lost |
| 3     | WalkoverWin   | Opponent withdrew / did not appear |
| 4     | Disqualified  | Hansoku-make — three Shido or grave violation |

Used in `tournament_match_competitors.result`.

### `MatchOutcomeEnum`

How the match was decided. Set when the match status → Completed.

| Value | Case          |
|-------|---------------|
| 0     | Ippon         |
| 1     | WazaAri       |
| 2     | Decision      |
| 3     | HansokuMake   |
| 4     | Walkover      |
| 5     | Bye           |
| 6     | GoldenScore   |

Used in `tournament_matches.match_outcome`.

### `MatchEventTypeEnum`

| Value | Case              |
|-------|-------------------|
| 0     | Ippon             |
| 1     | WazaAri           |
| 2     | Shido             |
| 3     | HansokuMake       |
| 4     | HansokuMakeLight  |
| 5     | Kinsa             |
| 6     | Note              |

Used in `tournament_match_events.event_type`.

---

## Referee

### `RefereeRoleEnum` — `app/System/Referee/Foundation/Enums/`

| Value | Case   |
|-------|--------|
| 0     | Center |
| 1     | Side   |
| 2     | Video  |

Used in `tournament_match_referees.role`.

### `RefereeCertificationLevelEnum` — `app/System/Referee/Foundation/Enums/`

| Value | Case          |
|-------|---------------|
| 0     | Club          |
| 1     | National      |
| 2     | Continental   |
| 3     | International |

Used in `referees.certification_level`.

---

## Financial

### `PaymentMethodEnum` — `app/System/Tournament/Foundation/Enums/`

| Value | Case   |
|-------|--------|
| 0     | Cash   |
| 1     | Bank   |
| 2     | Online |

Used in `tournament_registration_payments.method`.

---

## Belt Rank

### `BeltRankKindEnum` — `app/System/BeltRank/Foundation/Enums/`

| Value | Case |
|-------|------|
| 0     | Kyu  |
| 1     | Dan  |

Used in `belt_ranks.kind`.

---

## Convention

- Cases are **TitleCase** (`Pending`, `InProgress`). No `SNAKE_CASE`.
- Always backed (`enum X: int { ... }`).
- Cast via the `casts()` method on the model:

  ```php
  protected function casts(): array
  {
      return [
          'status' => TournamentStatusEnum::class,
          'level'  => TournamentLevelEnum::class,
      ];
  }
  ```

- TypeScript mirrors the integer values (not the case names) so frontend ↔ backend stay in sync.
