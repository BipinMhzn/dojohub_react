# TournamentRegistration

**File:** `app/System/Tournament/Models/TournamentRegistration.php`  
**Namespace:** `App\System\Tournament\Models`  
**Table:** `tournament_registrations`  
**Extends:** `AppBaseModel`

Records a player's entry into a specific tournament category. Captures the player's age and weight **at the time of registration** (snapshot, not live data).

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `tournament_category_id` | `ulid` | FK → `tournament_categories.id` |
| `club_id` | `ulid\|null` | FK → `clubs.id` (club the player represents) |
| `player_id` | `ulid` | FK → `players.id` |
| `player_age` | `integer` | Age at time of registration (calculated from `dob`) |
| `player_weight` | `decimal\|null` | Weight at registration (copied from player record) |
| `status` | `RegistrationStatusEnum` | Set by system |

---

## Casts

```php
'status' => RegistrationStatusEnum::class,
```

---

## RegistrationStatusEnum

| Case | Value | Meaning |
|------|-------|---------|
| `Registered` | `0` | Default on creation |
| `Confirmed` | `1` | Registration confirmed (future workflow) |
| `Withdrawn` | `2` | Withdrawn; excluded from bracket generation |

---

## Registration flow (RegistrationsController)

1. Only allowed when `category.status === CategoryStatusEnum::Pending`
2. `player_age` = `Carbon::parse($player->dob)->age` at time of registration
3. `player_weight` = `$player->weight` snapshot
4. `status` = `RegistrationStatusEnum::Registered`
5. `tournament_category_id` = from URL param

Duplicate registration check is handled by `RegistrationRepository::getRegisteringPlayerById()` which throws `PlayerAlreadyRegisteredException` if the player is already registered.

---

## Bracket seeding

When a bracket is generated, `Withdrawn` registrations are excluded:

```php
$registrations = $category->registrations()
    ->where('status', '!=', RegistrationStatusEnum::Withdrawn)
    ->with('player')
    ->get();
```

Top 3 registrations (by insertion order) are seeded 1–3; rest are randomly shuffled.

---

## Relationships

```php
public function category(): BelongsTo
// tournament_category_id → tournament_categories.id

public function club(): BelongsTo
// club_id → clubs.id

public function player(): BelongsTo
// player_id → players.id
```

---

## Related

- [TournamentCategory](TournamentCategory.md)
- [TournamentMatchCompetitor](TournamentMatchCompetitor.md)
- [Modules/Tournaments/RegistrationsController.md](../Modules/Tournaments/RegistrationsController.md)
