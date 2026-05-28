# Backend Architecture Guideline

How to structure backend code in dojohub_react. **Read [wiki/Architecture.md](../../wiki/Architecture.md) first** — this guideline focuses on enforcement rules and concrete examples.

---

## System vs Module model split

Every domain entity has **two** Eloquent models:

| Layer | Path | Purpose |
|-------|------|---------|
| **System model** | `app/System/{Entity}/Models/{Entity}.php` | Canonical — `$fillable`, `$sortableColumns`, casts, relationships, factory binding |
| **Module model** | `app/Modules/{Plural}/Models/{Entity}.php` | Thin extension point. Usually a one-line subclass |

**Rule:** Module code always references the **module-level** class, never the System class directly.

```php
// ✅ Correct — inside app/Modules/Venues/
use App\Modules\Venues\Models\Venue;

// ❌ Wrong — never reach across the boundary
use App\System\Venue\Models\Venue;
```

Both extend `App\System\AppBaseModel`, which bundles:
- `HasUlids` (ULID primary keys)
- `SoftDeletes`
- `HasFactory`
- `EditorLogs` (audit trait — populates `created_by`, `updated_by`, `deleted_by` from `Auth::user()`)
- `Auditable` (full audit log via owen-it/laravel-auditing)

---

## Repository pattern

**Controllers depend on the interface**, not the concrete repository class. Bindings live in each module's `ServiceProvider.php`.

```php
// In Controller — depend on Interface
public function __construct(private readonly VenueInterface $repository) {}

// In ServiceProvider — bind Interface → Repository
$this->app->bind(VenueInterface::class, fn () => new VenueRepository(
    new Venue,
    new Address,     // multi-model repositories inject every model they touch
));
```

`BaseSystemProvider` (in `base_system/`) auto-discovers all module ServiceProviders — no manual registration needed in `config/app.php`.

---

## `onBeforeResult()` hook for eager loading

Repositories extend `BaseSystem\Repositories\BaseRepository`. The `getList()` method routes through `onBeforeResult($builder)` for eager-loading and counts:

```php
public function onBeforeResult(Builder $builder): void
{
    $builder->with('address.country')->withCount('mats');
}
```

**Critical gotcha:** `getById()` does NOT call `onBeforeResult()`. If you need the same eager-load on the detail page, override `getById()`:

```php
public function getById(string $id, array $attributes = []): Venue
{
    return $this->model->newQuery()
        ->with(['address.country', 'mats'])
        ->findOrFail($id);
}
```

---

## Multi-table writes belong in the repository

When a single user action touches multiple tables (Venue + Address, Club + Address + Logo), the **repository** owns the transaction. The controller stays thin.

```php
// ✅ Controller — thin
public function store(VenueCreateRequest $request): RedirectResponse
{
    $venue = $this->repository->createVenue($request->validated());
    return to_route('venues.show', $venue->id)->with('success', 'Venue created successfully.');
}

// ✅ Repository — owns the multi-table transaction
public function createVenue(array $validated): Venue
{
    DB::beginTransaction();
    try {
        $address = $this->address->newQuery()->create([...]);
        $venue = $this->model->newQuery()->create([
            'name' => $validated['name'],
            'address_id' => $address->id,
        ]);
        DB::commit();
    } catch (Exception $exception) {
        DB::rollBack();
        throw $exception;
    }
    return $venue;
}
```

**Never** call `Address::create(...)` from a controller. **Never** wrap a transaction in the controller.

---

## ULID primary keys

- All domain tables use ULID PKs (`HasUlids` trait, auto-applied via `AppBaseModel`)
- Migration: `$table->ulid('id')->primary();`
- Foreign keys: `$table->ulid('venue_id');` then `->foreign('venue_id')->references('id')->on('venues')->restrictOnDelete();`
- **Exception:** the `users` table keeps Laravel's default auto-increment `bigint` PK

ULIDs are URL-safe, sortable, and don't expose row counts. Don't switch to UUID or auto-increment without team discussion.

---

## Soft-delete + FK RESTRICT

The pattern: DB columns are `ON DELETE RESTRICT`, but Eloquent's `SoftDeletes` issues an UPDATE (sets `deleted_at`) — which doesn't trip the FK. So you can soft-delete a parent even when restricted children exist.

```php
// ✅ Soft-delete the related row first, then the parent
$venue->address?->delete();
$venue->delete();
```

**Never** call `forceDelete()` in module code unless you've audited every FK referencing the row. See [Conventions.md](../../wiki/Conventions.md#soft-delete--fk-restrict).

---

## Module directory skeleton

```
app/Modules/{Plural}/
├── Controllers/          # Thin — delegate to repository, return Inertia\Response or RedirectResponse
├── Exceptions/           # Module-specific exceptions (e.g. VenueInUseException)
├── Filters/              # extends BaseFilter — public methods become query-string filters
├── Interfaces/           # extends BaseInterface — declares repository contract
├── Listeners/            # Event subscribers (registered in ServiceProvider::boot())
├── Models/               # Thin module-level models extending System models
├── Repositories/         # extends BaseRepository — implements Interface
├── Requests/             # extends BaseWebRequest / BaseWebListRequest
├── Routes/
│   ├── api.php           # (optional) JSON API routes
│   └── web.php           # Inertia routes — auth+verified middleware, manage prefix
└── ServiceProvider.php   # Binds Interface → Repository (auto-registered by BaseSystemProvider)
```

---

## Scaffold a new module

```bash
php artisan base-system:generate Tournament --web   # Inertia (web) module
php artisan base-system:generate Tournament         # JSON API module
```

The generator does NOT produce migrations — write those separately. See [base_system/README.md](../../base_system/README.md).

---

## Common pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| Eager-loading only in `onBeforeResult`, then `getById` misses relations | Override `getById()` too |
| Controller doing multi-table writes | Move to a repository method (`createVenue`, `updateVenue`) |
| Module model referencing System namespace directly | Always import `App\Modules\{Plural}\Models\X` |
| Calling `Auth::user()` inside model audit columns | Already automatic — `EditorLogs` trait handles it |
| `Route::resource('clubs')` PLUS POST-for-update | Use `->except('update')` then register POST explicitly |
| Hard-delete (`forceDelete`) without auditing FKs | Use soft-delete; FK RESTRICT blocks hard-delete on purpose |

---

## Related docs

- [wiki/Architecture.md](../../wiki/Architecture.md) — Full architecture reference
- [wiki/Conventions.md](../../wiki/Conventions.md) — All conventions
- [wiki/Database.md](../../wiki/Database.md) — Schema reference
- [base_system/README.md](../../base_system/README.md) — Base classes + generator
