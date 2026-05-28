# TournamentMatch

**File:** `app/System/Tournament/Models/TournamentMatch.php`  
**Namespace:** `App\System\Tournament\Models`  
**Table:** `tournament_matches`  
**Extends:** `AppBaseModel`

A single match within a bracket. Tracks round position, mat assignment, schedule, result, and routing to subsequent matches (winner/loser progression).

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `bracket_id` | `ulid` | FK → `tournament_brackets.id` |
| `bracket_section` | `BracketSectionEnum` | Main / Repechage / Bronze / GoldFinal |
| `round_number` | `integer` | Round within the bracket (1 = first round) |
| `round_name` | `string\|null` | Human label (e.g. "Quarter-Final") |
| `match_number` | `integer` | Position within the round |
| `mat_id` | `ulid\|null` | FK → `venue_mats.id`, assigned mat |
| `scheduled_at` | `datetime\|null` | Scheduled start time |
| `completed_at` | `datetime\|null` | Set when result is recorded |
| `status` | `MatchStatusEnum` | `Pending=0`, `Active=1`, `Completed=2` |
| `winner_id` | `ulid\|null` | FK → `players.id` |
| `winner_next_match_id` | `ulid\|null` | Self-referential FK — where winner goes |
| `winner_next_position` | `CompetitorPositionEnum\|null` | White/Blue slot in next match |
| `loser_next_match_id` | `ulid\|null` | Self-referential FK — where loser goes (repechage) |
| `loser_next_position` | `CompetitorPositionEnum\|null` | Position in loser's next match |
| `notes` | `string\|null` | Optional match notes |

---

## Casts

```php
'bracket_section' => BracketSectionEnum::class,
'status'          => MatchStatusEnum::class,
'scheduled_at'    => 'datetime',
'completed_at'    => 'datetime',
```

---

## BracketSectionEnum

| Case | Value | Meaning |
|------|-------|---------|
| `Main` | `0` | Main draw |
| `Repechage` | `1` | Consolation bracket |
| `Bronze` | `2` | Bronze medal match |
| `GoldFinal` | `3` | Gold/silver final |

---

## MatchStatusEnum

| Case | Value |
|------|-------|
| `Pending` | `0` |
| `Active` | `1` |
| `Completed` | `2` |

---

## Sortable columns

`round_number`, `match_number`, `scheduled_at`, `created_at`, `updated_at`

---

## Relationships

```php
public function bracket(): BelongsTo
// bracket_id → tournament_brackets.id

public function winner(): BelongsTo
// winner_id → players.id

public function winnerNextMatch(): BelongsTo
// winner_next_match_id → tournament_matches.id (self-referential)

public function loserNextMatch(): BelongsTo
// loser_next_match_id → tournament_matches.id (self-referential)

public function competitors(): HasMany
// match_id on tournament_match_competitors
```

---

## Result recording

When `BracketService::recordMatchResult()` is called:

1. `winner_id` is set from the winning competitor's `registration.player_id`
2. `status` → `Completed`
3. `completed_at` → `now()`
4. Winner is placed into `winner_next_match_id` at `winner_next_position`
5. Competitor `result` fields are updated (`Win` / `Loss`)

---

## Related

- [TournamentMatchCompetitor](TournamentMatchCompetitor.md)
- [TournamentBracket](TournamentBracket.md)
- [Modules/Tournaments/MatchesController.md](../Modules/Tournaments/MatchesController.md)
