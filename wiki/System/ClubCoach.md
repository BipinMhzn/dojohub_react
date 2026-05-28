# ClubCoach

**File:** `app/System/ClubCoach/Models/ClubCoach.php`  
**Namespace:** `App\System\ClubCoach\Models`  
**Table:** `clubs_coaches`  
**Extends:** `AppBaseModel`

Pivot/membership table linking a Coach to a Club. A coach cannot be assigned to the same club twice while still active (checked per-club, not globally — unlike players which are restricted globally).

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `club_id` | `ulid` | FK → `clubs.id` |
| `coach_id` | `ulid` | FK → `coaches.id` |
| `started_at` | `datetime` | Membership start date |
| `ended_at` | `datetime\|null` | Null = currently active |

---

## Casts

```php
'started_at' => 'datetime',
'ended_at'   => 'datetime',
```

---

## Business rule: no duplicate active membership at same club

Enforced in `ClubCoachRepository::assignCoach()`:

```php
$alreadyActive = $this->model->newQuery()
    ->where('club_id', $clubId)
    ->where('coach_id', $validated['coach_id'])
    ->whereNull('ended_at')
    ->exists();

if ($alreadyActive) {
    throw new \RuntimeException('This coach already has an active membership at this club.');
}
```

Note: A coach CAN coach multiple clubs simultaneously (unlike players).

---

## Relationships

```php
public function club(): BelongsTo
// club_id → clubs.id

public function coach(): BelongsTo
// coach_id → coaches.id
```

---

## Related

- [Club](Club.md)
- [Coach](Coach.md)
- [Modules/Clubs/ClubCoachesController.md](../Modules/Clubs/ClubCoachesController.md)
