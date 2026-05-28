# BaseInterface — Wiki

**File**: `base_system/src/Interfaces/BaseInterface.php`  
**Namespace**: `BaseSystem\Interfaces`

---

## Purpose

The CRUD contract that every module repository must fulfill. Module-level interfaces extend `BaseInterface` and may add domain-specific methods. `BaseRepository` implements `BaseInterface` — so concrete repositories inherit all method implementations and only need to override what differs.

---

## Interface Definition

```php
interface BaseInterface
{
    public function create(array $attributes): BaseModel;

    public function update(string $id, array $attributes): BaseModel;

    public function getById(string $id, array $attributes = []): BaseModel;

    public function getList(
        array $params = [],
        bool $paginate = true,
        array $attributes = []
    ): LengthAwarePaginator|Collection;

    public function destroy(string $id, array $attributes = []): true;
}
```

---

## Method Contracts

| Method | Parameters | Returns | Throws |
|--------|-----------|---------|--------|
| `create` | `$attributes` — validated form data | Saved `BaseModel` | — |
| `update` | `$id` — ULID, `$attributes` — validated data | Updated `BaseModel` | `ModelNotFoundException` |
| `getById` | `$id` — ULID, `$attributes` — select columns | Found `BaseModel` | `ModelNotFoundException` |
| `getList` | `$params` — sort/filter/page, `$paginate`, `$attributes` | Paginator or Collection | — |
| `destroy` | `$id` — ULID, `$attributes` — extra data | `true` | `ModelNotFoundException` |

---

## Module Interface Pattern

Each module declares its own interface that extends `BaseInterface`:

```php
// app/Modules/Venues/Interfaces/VenueInterface.php
interface VenueInterface extends BaseInterface
{
    public function createVenue(array $validated): Venue;
    public function updateVenue(array $validated, string $venueId): Venue;
    public function deleteVenue(string $venueId): true;
}
```

The custom methods handle multi-table writes (e.g. creating `Address` + `Venue` atomically). When a module has no custom behaviour, the interface body is empty:

```php
// app/Modules/Players/Interfaces/PlayerInterface.php
interface PlayerInterface extends BaseInterface
{
    // All operations delegated directly to BaseRepository methods
}
```

---

## ServiceProvider Binding

Each module's `ServiceProvider.php` binds the interface to its concrete repository:

```php
// app/Modules/Venues/ServiceProvider.php
public function register(): void
{
    $this->app->bind(VenueInterface::class, VenueRepository::class);
}
```

Controllers type-hint the interface in their constructor:

```php
public function __construct(private readonly VenueInterface $repository)
```

---

## Related

- [BaseRepository](BaseRepository.md) — abstract class implementing this interface
- [BaseSystemProvider](BaseSystemProvider.md) — registers all ServiceProviders that bind interfaces
