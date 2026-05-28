# TournamentMatchCompetitor

**File:** `app/System/Tournament/Models/TournamentMatchCompetitor.php`  
**Namespace:** `App\System\Tournament\Models`  
**Table:** `tournament_match_competitors`  
**Extends:** `AppBaseModel`

One competitor slot in a match. Each match has two rows: one for `White` position and one for `Blue` position. A slot may be empty (`registration_id = null`) for bye matches.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `match_id` | `ulid` | FK → `tournament_matches.id` |
| `registration_id` | `ulid\|null` | FK → `tournament_registrations.id` |
| `position` | `CompetitorPositionEnum` | `White=0` or `Blue=1` |
| `seed` | `integer\|null` | Seeding number at time of bracket generation |
| `score` | `integer\|null` | Match score (waza-ari count or points) |
| `penalties` | `integer\|null` | Shido/penalty count |
| `result` | `CompetitorResultEnum` | Outcome for this competitor |

---

## Casts

```php
'position' => CompetitorPositionEnum::class,
'result'   => CompetitorResultEnum::class,
```

---

## CompetitorPositionEnum

| Case | Value |
|------|-------|
| `White` | `0` |
| `Blue` | `1` |

---

## CompetitorResultEnum

| Case | Value | Meaning |
|------|-------|---------|
| `Pending` | `0` | Match not yet completed |
| `Win` | `1` | Won this match |
| `Loss` | `2` | Lost this match |
| `WalkoverWin` | `3` | Won by walkover (opponent absent) |
| `Disqualified` | `4` | Disqualified |

---

## Sortable columns

`created_at`, `updated_at`

---

## Relationships

```php
public function match(): BelongsTo
// match_id → tournament_matches.id

public function registration(): BelongsTo
// registration_id → tournament_registrations.id
```

---

## Bye slots

When bracket generation creates a bye (e.g. 5 competitors → 8-slot bracket, 3 byes), three competitor slots have `registration_id = null`. The bye match is auto-completed with the real competitor advancing.

---

## Related

- [TournamentMatch](TournamentMatch.md)
- [TournamentRegistration](TournamentRegistration.md)
