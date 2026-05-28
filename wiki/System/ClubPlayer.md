# ClubPlayer

**File:** `app/System/ClubPlayer/Models/ClubPlayer.php`  
**Namespace:** `App\System\ClubPlayer\Models`  
**Table:** `clubs_players`  
**Extends:** `AppBaseModel`

The pivot/membership table linking a Player to a Club. Membership is time-bounded (`started_at` / `ended_at`). A player can have multiple memberships over time, but only one active (no `ended_at`) membership at a time across all clubs.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `club_id` | `ulid` | FK → `clubs.id` |
| `player_id` | `ulid` | FK → `players.id` |
| `belt_rank_id` | `ulid\|null` | FK → `belt_ranks.id`, belt rank at time of membership |
| `grade_certificate` | `string\|null` | Certificate reference number or path |
| `started_at` | `datetime` | Membership start date |
| `ended_at` | `datetime\|null` | Null = currently active |

---

## Casts

```php
'started_at' => 'datetime',
'ended_at'   => 'datetime',
```

---

## Business rule: one active club

A player cannot be in two active memberships simultaneously. Enforced in `ClubPlayerRepository::assignPlayer()`:

```php
$alreadyActive = $this->model->newQuery()
    ->where('player_id', $validated['player_id'])
    ->whereNull('ended_at')
    ->exists();

if ($alreadyActive) {
    throw new \RuntimeException('This player already has an active membership at another club.');
}
```

This `RuntimeException` is caught in `ClubPlayersController::store()` and returned as a field error on `player_id`.

---

## Relationships

```php
public function club(): BelongsTo
// club_id → clubs.id

public function player(): BelongsTo
// player_id → players.id

public function beltRank(): BelongsTo
// belt_rank_id → belt_ranks.id
```

---

## Related

- [Club](Club.md)
- [Player](Player.md)
- [BeltRank](BeltRank.md)
- [Modules/Clubs/ClubPlayersController.md](../Modules/Clubs/ClubPlayersController.md)
