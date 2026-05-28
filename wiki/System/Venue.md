# Venue

**File:** `app/System/Venue/Models/Venue.php`  
**Namespace:** `App\System\Venue\Models`  
**Table:** `venues`  
**Extends:** `AppBaseModel`

A physical venue where tournaments are held. Has one address and many mats.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `name` | `string` | Display name, required, max 250 |
| `address_id` | `ulid\|null` | FK → `addresses.id` ON DELETE RESTRICT |

---

## Sortable columns

`name`, `created_at`, `updated_at`

---

## Relationships

```php
public function address(): BelongsTo
// address_id → addresses.id

public function mats(): HasMany
// venue_id on venue_mats
```

---

## Eager loading strategy

The `VenueRepository` always eager-loads:

- `address.country` on list queries (`onBeforeResult`)
- `address.country` + `mats` on single-record queries (`getById`)

List queries also attach `withCount('mats')`.

---

## Related

- [VenueMat](VenueMat.md)
- [Address](Address.md)
- [Modules/Venues/Index.md](../Modules/Venues/Index.md)
