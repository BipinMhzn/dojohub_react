# VenueMat

**File:** `app/System/VenueMat/Models/VenueMat.php`  
**Namespace:** `App\System\VenueMat\Models`  
**Table:** `venue_mats`  
**Extends:** `AppBaseModel`

An individual competition mat within a venue. Mat numbers must be unique per venue.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `venue_id` | `ulid` | FK → `venues.id` ON DELETE RESTRICT |
| `mat_number` | `integer` | 1–99, unique per venue (including soft-deleted) |
| `display_name` | `string\|null` | Optional display override (e.g. "Tatami 1") |

---

## Casts

```php
'mat_number' => 'integer',
```

---

## Unique constraint

`mat_number` is unique per `venue_id` (soft-delete aware — the validation rule uses `whereNull('deleted_at')` to avoid conflicts with soft-deleted mats).

---

## Relationships

```php
public function venue(): BelongsTo
// venue_id → venues.id
```

---

## Related

- [Venue](Venue.md)
- [Modules/Venues/VenueMatController.md](../Modules/Venues/VenueMatController.md)
