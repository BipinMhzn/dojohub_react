# VenueRepository

**File:** `app/Modules/Venues/Repositories/VenueRepository.php`  
**Implements:** `VenueInterface`  
**Extends:** `BaseRepository`

Handles all data access for venues, including transactional creation/update/deletion of the embedded address.

---

## Constructor

```php
public function __construct(Venue $model, private Address $address)
```

Injects the `Address` model separately to create address rows in transactions.

---

## `getFilter(Builder $builder): VenuesFilter`

Returns a `VenuesFilter` instance. Called by `BaseRepository::getList()`.

---

## `onBeforeResult(Builder $builder): void`

Called automatically by `BaseRepository::getList()` before paginating list results:

```php
$builder->with('address.country')->withCount('mats');
```

Every item in list results includes the eager-loaded address + country + mat count.

---

## `getById(string $id, array $attributes = []): Venue`

Single-record query. Loads more relations than the list:

```php
$this->model->newQuery()
    ->with(['address.country', 'mats'])
    ->findOrFail($id);
```

Throws `ModelNotFoundException` (→ 404) if not found.

---

## `createVenue(array $validated): Venue`

Creates both an `Address` row and a `Venue` row inside a single `DB::transaction()`.

```
BEGIN
  INSERT INTO addresses (address_line_1, city, country_code, ...) → $address
  INSERT INTO venues (name, address_id) → $venue
COMMIT
```

Rolls back and re-throws any exception.

Returns the created `Venue`.

---

## `updateVenue(array $validated, string $venueId): Venue`

Updates the venue and its address in a `DB::transaction()`:

```
BEGIN
  UPDATE addresses SET ... WHERE id = $venue->address_id
  UPDATE venues SET name = ... WHERE id = $venueId
COMMIT
```

Fetches the venue first (`findOrFail`), then updates `$venue->address` in place.

---

## `deleteVenue(string $venueId): true`

Soft-deletes both address and venue in a `DB::transaction()`:

```
BEGIN
  UPDATE addresses SET deleted_at = now() WHERE id = $venue->address_id
  UPDATE venues SET deleted_at = now() WHERE id = $venueId
COMMIT
```

Throws `VenueInUseException` if the venue cannot be deleted (caught in the controller).

---

## VenueMatRepository

**File:** `app/Modules/Venues/Repositories/VenueMatRepository.php`  
**Implements:** `VenueMatInterface`

Simpler repository — no filter, no address transaction.

### `createMat(string $venueId, array $validated): VenueMat`

```php
$this->model->newQuery()->create([
    'venue_id'     => $venueId,
    'mat_number'   => $validated['mat_number'],
    'display_name' => $validated['display_name'] ?? null,
]);
```

### `updateMat(string $matId, array $validated): VenueMat`

Finds by ID and updates `mat_number` + `display_name`.

### `deleteMat(string $matId): true`

Soft-deletes the mat. Throws `VenueMatInUseException` if in use (e.g. scheduled matches reference it).

---

## Related

- [VenuesController](VenuesController.md)
- [VenueMatController](VenueMatController.md)
- [System/Venue](../../System/Venue.md)
- [architecture.md](../../../.claude/guidelines/architecture.md#repository-pattern)
