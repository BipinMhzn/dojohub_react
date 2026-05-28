# BaseModel — Wiki

**File**: `base_system/src/Foundation/Database/BaseModel.php`  
**Namespace**: `BaseSystem\Foundation\Database`  
**Extends**: `Illuminate\Database\Eloquent\Model`

---

## Purpose

Abstract base Eloquent model. Adds sort configuration, per-page defaults, and sort-key aliasing on top of standard Laravel Model. All module-level and system-level models extend from this (either directly or via `AppBaseModel`).

---

## Properties

| Property | Type | Default | Purpose |
|----------|------|---------|---------|
| `$defaultSortColumn` | string | `'name'` | Column used when no `sort_by` param supplied |
| `$defaultSortOrder` | string | `'asc'` | Direction used when no `sort_order` param supplied |
| `$sortableColumns` | array | `[]` | Whitelist of columns that `getList` allows sorting on |
| `$sortKeyMaps` | array | `[]` | Maps UI key → actual column (e.g. `'venue_name' => 'venues.name'`) |
| `$perPage` | int | `10` | Laravel default per-page (overrides Eloquent's 15) |

---

## Methods

| Method | Signature | Notes |
|--------|-----------|-------|
| `sortableColumns()` | `(): array` | Returns `$sortableColumns` |
| `defaultSortColumn()` | `(): string` | Returns `$defaultSortColumn` |
| `defaultSortOrder()` | `(): string` | Returns `$defaultSortOrder` |
| `mapSortKey($key)` | `(string): string` | Returns mapped column or `$key` unchanged |

---

## Usage in System Models

System models override `$sortableColumns` and `$defaultSortColumn` to match their table:

```php
// app/System/Tournament/Models/Tournament.php
protected string $defaultSortColumn = 'start_at';
protected string $defaultSortOrder  = 'desc';
protected array  $sortableColumns   = ['name', 'start_at', 'created_at'];
```

```php
// app/System/Coach/Models/Coach.php
protected string $defaultSortColumn = 'full_name';
protected array  $sortableColumns   = ['full_name', 'created_at'];
```

---

## Sort Key Mapping

`$sortKeyMaps` allows the UI to send a human-readable sort key that maps to a qualified column:

```php
// Example: sorting a list of registrations by player name
protected array $sortKeyMaps = [
    'player_name' => 'players.name',
];
```

`BaseRepository::attachOrderByQuery()` calls `$model->mapSortKey($key)` before building the `ORDER BY` clause.

---

## Inheritance Chain

```
BaseModel (base_system)
└── AppBaseModel (app/System/Foundation/)
    ├── + HasUlids
    ├── + SoftDeletes
    ├── + HasFactory
    ├── + EditorLogs
    └── + Auditable (owen-it/laravel-auditing)
        └── All domain System models
            └── Module-level model aliases
```

The `User` model extends `BaseModel` directly (not `AppBaseModel`) because it uses `bigIncrements` IDs rather than ULIDs and does not use soft-deletes.

---

## Related

- [AppBaseModel](../System/AppBaseModel.md) — adds ULID PKs, SoftDeletes, EditorLogs, Auditable
- [EditorLogs](EditorLogs.md) — trait that auto-fills `created_by`/`updated_by`/`deleted_by`
- [BaseRepository](BaseRepository.md) — uses sort properties from BaseModel
