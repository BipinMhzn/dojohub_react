# Address

**File:** `app/System/Address/Models/Address.php`  
**Namespace:** `App\System\Address\Models`  
**Table:** `addresses`  
**Extends:** `AppBaseModel`

Shared address entity used by Venue, Club, and Player. Addresses are standalone rows linked by FK; they are created and deleted as part of the parent entity's transaction.

---

## Fillable

| Column | Type | Rules |
|--------|------|-------|
| `address_line_1` | `string` | Required for Venue, nullable for Club/Player |
| `address_line_2` | `string\|null` | Optional second line |
| `city` | `string` | Required for Venue, nullable for Club/Player |
| `state` | `string\|null` | Optional state/province |
| `postal_code` | `string\|null` | Optional |
| `country_code` | `string(2)` | ISO-3166 alpha-2, FK → `countries.code` |
| `latitude` | `float\|null` | Decimal degrees |
| `longitude` | `float\|null` | Decimal degrees |

---

## Casts

```php
'latitude'  => 'float',
'longitude' => 'float',
```

---

## Sortable columns

`city`, `country_code`, `created_at`

---

## Relationships

```php
public function country(): BelongsTo
// country_code → countries.code
```

---

## Lifecycle

Addresses are not created or deleted independently. They are always managed by the parent entity's repository inside a `DB::transaction()`:

- **Venue** — required address; created on `createVenue()`, updated on `updateVenue()`, deleted on `deleteVenue()`
- **Club** — optional address; only created if `address_line_1` is present
- **Player** — not currently linked via address (no `address_id` on player table at time of writing; referenced by `Player` model but populated via separate flow)

---

## Related

- [Venue](Venue.md), [Club](Club.md), [Player](Player.md)
- [VenueRepository](../Modules/Venues/VenueRepository.md) — example of transactional address management
