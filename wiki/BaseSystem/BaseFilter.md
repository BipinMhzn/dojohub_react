# BaseFilter — Wiki

**File**: `base_system/src/Filters/BaseFilter.php`  
**Namespace**: `BaseSystem\Filters`

---

## Purpose

Abstract base class for all module filter classes. Receives a query `Builder` at construction and attaches WHERE conditions dynamically — one method per filterable parameter key. Called by `BaseRepository::getList()` as part of the list pipeline.

---

## Constructor

```php
public function __construct(protected Builder $builder)
```

The builder is passed in from `BaseRepository::getList()` after the ORDER BY clause is already applied.

---

## `attachFilterQuery(array $filterOptions): Builder`

Core dispatch method. For each key in `$filterOptions`, if a method of the same name exists on the filter class, it is called with the value:

```php
foreach ($filerOptions as $key => $value) {
    if (method_exists($this, $key)) {
        call_user_func_array([$this, $key], array_filter([$value], 'strlen'));
    }
}
```

**Important:** `array_filter([$value], 'strlen')` removes empty-string values — if `$value` is `''`, the method is NOT called. This prevents accidental empty-string filters from silently matching everything.

---

## Built-in Methods

| Method | Filter behaviour |
|--------|-----------------|
| `title(string $title)` | `WHERE title LIKE %{title}%` |
| `name(string $name)` | `WHERE name LIKE %{name}%` |

These are available in every concrete filter class automatically.

---

## Abstract Method

```php
abstract public function keyword(string $keyword): void;
```

Every module filter **must** implement `keyword()`. This is the free-text search entrypoint. Typical implementation:

```php
// VenuesFilter
public function keyword(string $keyword): void
{
    $this->builder->where('name', 'like', "%{$keyword}%");
}

// PlayersFilter — searches by name
public function keyword(string $keyword): void
{
    $this->builder->where('name', 'like', "%{$keyword}%");
}

// CoachesFilter — searches by full_name
public function keyword(string $keyword): void
{
    $this->builder->where('full_name', 'like', "%{$keyword}%");
}
```

---

## Adding Custom Filters

Add a public method whose name matches the request parameter key:

```php
// In ClubsFilter — filter by country
public function country(string $country): void
{
    $this->builder->whereHas('address', function ($q) use ($country) {
        $q->where('country_code', $country);
    });
}
```

When `country=NP` is in the request params, `attachFilterQuery` calls `$this->country('NP')` automatically.

---

## Parameter Flow

```
Request params → BaseWebListRequest validates → controller passes to repository
    → getList($params)
        → attachOrderByQuery($params)   ← sort_by, sort_order, per_page consumed
        → getFilter($builder)           ← concrete filter instantiated
        → filter->attachFilterQuery($params)  ← keyword, name, custom filters applied
        → onBeforeResult($builder)      ← eager loading
        → paginate() / get()
```

---

## Related

- [BaseRepository](BaseRepository.md) — calls `getFilter()` and `attachFilterQuery()`
- [BaseWebListRequest](BaseWebListRequest.md) — validates the params before they reach the filter
