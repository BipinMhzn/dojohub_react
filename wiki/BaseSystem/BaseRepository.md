# BaseRepository — Wiki

**File**: `base_system/src/Repositories/BaseRepository.php`  
**Namespace**: `BaseSystem\Repositories`  
**Implements**: `BaseInterface`

---

## Purpose

Abstract base class for all module repositories. Provides standard CRUD operations with built-in sorting, filtering, pagination, and event dispatching. Every module repository extends this class and injects its model via the constructor.

---

## Constructor

```php
public function __construct(protected BaseModel $model)
```

Module repositories call `parent::__construct($model)` and can inject additional models (e.g. `Address` for `ClubRepository`).

---

## Methods

### `getList(array $params, bool $paginate, array $attributes): LengthAwarePaginator|Collection`

The primary read method. Full flow:

1. Calls `attachOrderByQuery($params)` — creates `Builder`, applies `ORDER BY`
2. Calls `getFilter($builder)` — gets the concrete filter instance (abstract, must be implemented)
3. Calls `filter->attachFilterQuery($params)` — runs filter methods for each param
4. Checks `all=1` in params — forces `$paginate = false` to return all records
5. Calls `onBeforeResult($builder)` — hook for eager loading (default: no-op)
6. If `$attributes`, calls `$builder->select($attributes)`
7. Returns `$builder->paginate()` or `$builder->get()`

**Sorting (`attachOrderByQuery`):**
- `sort_by` param → validated against `$model->sortableColumns()`; falls back to `defaultSortColumn`
- `sort_order` param → validated as `asc|desc`; falls back to `defaultSortOrder`
- `per_page` param → calls `$model->setPerPage($perPage)`; default 10
- `sort_by` is mapped through `$model->mapSortKey()` before applying (allows alias → column)

### `create(array $attributes): BaseModel`

```php
$model = $this->model->newInstance();
$model->fill($attributes);
$this->dispatchEvent('creating', $model);
$model->save();
$this->dispatchEvent('created', $model);
return $model;
```

Dispatches `creating` and `created` events if registered in `$this->events`.

### `update(string $id, array $attributes): BaseModel`

```php
$model = $this->model->newQuery()->findOrFail($id);
$model->fill($attributes);
$this->dispatchEvent('updating', $model);
$model->save();
$this->dispatchEvent('updated', $model);
return $model;
```

### `getById(string $id, array $attributes): BaseModel`

```php
return $this->model->newQuery()->findOrFail($id);
```

Throws `ModelNotFoundException` (→ HTTP 404) if not found. Override in module repository to eager-load relationships.

### `destroy(string $id, array $attributes): true`

```php
$model = $this->model->newQuery()->findOrFail($id);
$this->dispatchEvent('deleting', $model);
$model->delete();
$this->dispatchEvent('deleted', $model);
return true;
```

For models using `SoftDeletes`, this sets `deleted_at`. For module repositories with related models (e.g. address), override `destroy` or declare a custom method in the interface.

---

## Abstract Method

```php
abstract public function getFilter(Builder $builder);
```

Must be implemented by every concrete repository. Returns the module's filter instance:

```php
// Example in VenueRepository
public function getFilter(Builder $builder): VenuesFilter
{
    return new VenuesFilter($builder);
}
```

---

## Hook: `onBeforeResult`

```php
public function onBeforeResult(Builder $builder): void
{
    // default: no-op
}
```

Override in module repository to eager-load relationships before every `getList` call:

```php
// Example in ClubRepository
public function onBeforeResult(Builder $builder): void
{
    $builder->with('address');
}
```

---

## Event System

```php
protected array $events = [];
```

Populate in the module repository to fire events:

```php
protected array $events = [
    'creating' => TournamentCreatingEvent::class,
    'created'  => TournamentCreatedEvent::class,
];
```

Events receive the model instance. Currently no modules define events by default.

---

## Override Pattern

Module repositories only need to override what differs from the base:

| Override need | Method |
|---------------|--------|
| Eager-load on list | `onBeforeResult()` |
| Eager-load on single | `getById()` |
| Multi-table write (create) | Custom `createX()` method |
| Multi-table write (update) | Custom `updateX()` method |
| Multi-table delete | Custom `deleteX()` method |
| Fire events | Set `$events` array |

---

## Related

- [BaseInterface](BaseInterface.md) — contract this class implements
- [BaseModel](BaseModel.md) — model class this class accepts
- [BaseFilter](BaseFilter.md) — filter class returned by `getFilter()`
