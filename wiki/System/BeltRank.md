# BeltRank

**File:** `app/System/BeltRank/Models/BeltRank.php`  
**Namespace:** `App\System\BeltRank\Models`  
**Table:** `belt_ranks`  
**Extends:** `Illuminate\Database\Eloquent\Model` (NOT `AppBaseModel`)

Reference data for judo belt ranks. Seeded once and rarely changed. No soft-delete, no audit trail, no editor tracking.

---

## Why not AppBaseModel

`BeltRank` is lookup/reference data, not a domain entity created by users. It has no `deleted_at`, no `created_by`/`updated_by`, and no audit trail. Using plain `Model` + `HasUlids` is intentional.

```php
public $timestamps = false;
```

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `code` | `string` | Unique short code (e.g. `"6KYU"`, `"1DAN"`) |
| `name` | `string` | Display name (e.g. "6th Kyu", "1st Dan") |
| `kind` | `BeltRankKindEnum` | `Kyu=0` or `Dan=1` |
| `color` | `string` | Belt color string (e.g. `"white"`, `"black"`) |
| `sort_order` | `integer` | Controls display order (lower = lower rank) |

---

## Casts

```php
'kind'       => BeltRankKindEnum::class,
'sort_order' => 'integer',
```

---

## BeltRankKindEnum

| Case | Value | Meaning |
|------|-------|---------|
| `Kyu` | `0` | Student grades (colour belts) |
| `Dan` | `1` | Expert/black belt grades |

---

## Usage in controllers

Belt ranks are fetched ordered by `sort_order` and projected to minimal columns:

```php
BeltRank::orderBy('sort_order')->get(['id', 'name', 'code', 'color'])
```

---

## Related

- [Player](Player.md) — `current_belt_rank_id` FK
- [ClubPlayer](ClubPlayer.md) — `belt_rank_id` on membership pivot
