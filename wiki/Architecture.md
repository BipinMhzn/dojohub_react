# Architecture

How modules are wired in DojoHub. This is the single most important doc to read before editing any module — most patterns are unstated in the code itself.

---

## The two-layer model split

Every domain entity has **two** Eloquent models:

| Layer | Location | Role |
|-------|----------|------|
| **System model** | `app/System/{Entity}/Models/{Entity}.php` | Canonical definition — `$fillable`, `$casts()`, relationships, enum casts |
| **Module model** | `app/Modules/{Plural}/Models/{Entity}.php` | Thin extension point — usually empty: `class Venue extends SystemVenue {}` |

Module code (controllers, repositories, factories) always references the **module-level** class. The module model exists so a module can override behaviour (scopes, mutators, local relationships) without touching the shared System model.

Concrete example for Venues:

```
app/System/Venue/Models/Venue.php            ← fillable, casts, address() relation
app/Modules/Venues/Models/Venue.php          ← extends System Venue (currently empty)
```

When a module needs **another module's** model (e.g. the Tournaments module needs `Player`, `Club`, `Venue`), it adds a thin module-level extension in its own `Models/` directory. The Tournaments module owns:

```
app/Modules/Tournaments/Models/Player.php    ← extends System Player
app/Modules/Tournaments/Models/Club.php      ← extends System Club
app/Modules/Tournaments/Models/Venue.php     ← extends System Venue
```

Never type-hint the System model in module code. Always use the Module model.

All models extend `AppBaseModel` (`app/System/AppBaseModel.php`), which in turn extends `BaseSystem\Foundation\Database\BaseModel` and bundles `HasUlids`, `SoftDeletes`, `HasFactory`, and the `EditorLogs` audit trait.

---

## Module directory layout

Every module under `app/Modules/{Plural}/` has the same skeleton:

```
app/Modules/{Plural}/
├── Controllers/        # Thin — delegate to repository, return Inertia\Response or RedirectResponse
├── Exceptions/         # Module-specific exception classes
├── Filters/            # Magic-method filters (extends BaseFilter)
├── Interfaces/         # Repository + service contracts (extend BaseInterface)
├── Models/             # Thin module-level models extending System models
├── Repositories/       # Data access (extends BaseRepository)
├── Requests/           # Form request classes (extends BaseWebRequest)
├── Routes/
│   ├── api.php         # Optional JSON API routes
│   └── web.php         # Inertia routes (auth + verified, manage prefix)
├── Services/           # Optional — multi-repository business logic
└── ServiceProvider.php # Binds interfaces to repositories
```

`SystemRouteProvider` and `BaseSystemProvider` auto-discover the `Routes/` files and the module `ServiceProvider.php` — no manual registration in `bootstrap/providers.php` is needed.

---

## Repository pattern

Every repository extends `BaseSystem\Repositories\BaseRepository` and implements a module interface that extends `BaseInterface`.

### What `BaseRepository` gives you

| Method | Behaviour |
|--------|-----------|
| `getList(array $params, bool $paginate, array $attributes)` | Sort + filter + paginate. Calls `getFilter()` and `onBeforeResult()` |
| `getById(string $id, array $attributes)` | `findOrFail($id)` |
| `create(array $attributes)` | New model, fill, save |
| `update(string $id, array $attributes)` | Find, fill, save |
| `destroy(string $id, array $attributes)` | Soft delete |

### What each repository must provide

**`getFilter(Builder $builder)`** — abstract. Returns the module's filter:

```php
public function getFilter(Builder $builder): VenuesFilter
{
    return new VenuesFilter($builder);
}
```

### Optional hooks

**`onBeforeResult(Builder $builder)`** — runs before `getList` materialises results. Use it to attach eager-loads or default scopes:

```php
public function onBeforeResult(Builder $builder): void
{
    $builder->with('address');
}
```

> If you eager-load in `onBeforeResult`, also override `getById` to do the same — it does **not** call `onBeforeResult`.

```php
public function getById(string $id, array $attributes = []): Venue
{
    return $this->model->newQuery()->with('address')->findOrFail($id);
}
```

### Custom lifecycle methods (multi-table writes)

When a module owns more than one table (Venue + Address, Club + Address, etc.), don't put the logic in the controller. Declare custom methods on the interface, implement them in the repository, and inject every involved model via the constructor.

```php
interface VenueInterface extends BaseInterface
{
    public function createVenue(array $validated): Venue;
    public function updateVenue(array $validated, string $venueId): Venue;
    public function deleteVenue(string $venueId): true;
}

class VenueRepository extends BaseRepository implements VenueInterface
{
    public function __construct(Venue $model, private Address $address)
    {
        parent::__construct($model);
    }

    public function createVenue(array $validated): Venue
    {
        $address = $this->address->newQuery()->create([
            'address' => $validated['address'],
            'city'    => $validated['city'],
            'state'   => $validated['state'],
            'country' => $validated['country'],
        ]);

        return $this->model->newQuery()->create([
            'name'       => $validated['name'],
            'mat_count'  => $validated['mat_count'],
            'address_id' => $address->id,
        ]);
    }
}
```

The controller then becomes a single line:

```php
public function store(VenueCreateRequest $request): RedirectResponse
{
    $this->repository->createVenue($request->validated());
    return to_route('venues.index')->with('success', 'Venue created successfully.');
}
```

---

## Interface + ServiceProvider binding

Every repository is bound to an interface in the module's `ServiceProvider.php`. Controllers depend on the **interface**, never the concrete repository.

```php
// app/Modules/Venues/ServiceProvider.php
public function register(): void
{
    $this->app->bind(VenueInterface::class, VenueRepository::class);
}
```

```php
// Controller
public function __construct(private readonly VenueInterface $repository) {}
```

This makes repositories swappable in tests and forces every public capability to be visible on the interface.

---

## Controllers

Two base controllers, picked by module type:

| Base | Use for | Returns |
|------|---------|---------|
| `BaseWebController` (`base_system/src/Controllers/BaseWebController.php`) | Inertia pages | `Inertia\Response` / `RedirectResponse` |
| `BaseController` (`base_system/src/Controllers/BaseController.php`) | JSON APIs | `JsonResponse` |

All current DojoHub modules use `BaseWebController`. Render via the protected helper, not the Inertia facade:

```php
public function index(BaseWebListRequest $request): Response
{
    return $this->render('venues/index', [
        'venues'  => $this->repository->getList($request->validated()),
        'filters' => $request->validated(),
    ]);
}
```

---

## Filters

Filters extend `BaseFilter`. Every public method on the class becomes a filter that fires when a query string parameter with the matching name is present.

```php
class VenuesFilter extends BaseFilter
{
    public function keyword(string $keyword): void
    {
        $this->builder->where('name', 'like', "%{$keyword}%");
    }
}
```

`keyword()` is required by `BaseFilter` (abstract). Add additional public methods for additional filterable params.

---

## Requests

| Base | Use case |
|------|----------|
| `BaseWebRequest` | Mutation forms on Inertia pages |
| `BaseWebListRequest` | List endpoints — all sort/page/keyword rules are `nullable` |
| `BaseRequest` / `BaseListRequest` | JSON API endpoints (rules return 417 on failure) |

Update requests extend create requests:

```php
class VenueUpdateRequest extends VenueCreateRequest { /* same rules */ }
```

---

## Data flow (Inertia module)

```
HTTP Request
    │
    ▼
Controller (extends BaseWebController)
    │  $this->render('module/page', $props)
    │
    ▼
Module Interface (constructor-injected)
    │
    ▼
Module Repository (extends BaseRepository)
    │  attachOrderByQuery → getFilter → filter.attachFilterQuery → onBeforeResult
    │
    ▼
Eloquent Builder → Database
    │  AppBaseModel + EditorLogs sets created_by/updated_by/deleted_by automatically
    │
    ▼
Inertia::render() → React page (props become component props)
    │  Mutations: redirect-back with session flash (success / errors)
```

---

## Scaffolding a new module

```bash
php artisan base-system:generate Tournament --web   # Inertia module
php artisan base-system:generate Tournament         # JSON API module
```

Either form generates the System model, module Controller/Repository/Interface/Filter/Requests/Model/ServiceProvider, and Routes scaffolding. **The generator does not create the migration** — write that separately.

See [../base_system/README.md](../base_system/README.md) for full stub reference.

---

## Where things live — cheat sheet

| Thing | Path |
|-------|------|
| Canonical schema definition | `app/System/{Entity}/Models/{Entity}.php` |
| Module-level model extension | `app/Modules/{Plural}/Models/{Entity}.php` |
| Enums | `app/System/{Entity}/Foundation/Enums/` |
| Module business logic | `app/Modules/{Plural}/Services/{Name}Service.php` |
| Audit trait | `BaseSystem\Foundation\Database\EditorLogs` (in `base_system/`) |
| File upload trait | `app/System/Foundation/Traits/FileUpload.php` |
| Migrations | `database/migrations/` |
| Factories | `database/factories/` |
| Seeders | `database/seeders/` |
| Feature tests | `tests/Feature/` |
