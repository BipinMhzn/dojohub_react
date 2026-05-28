# AppBaseModel

**File:** `app/System/AppBaseModel.php`  
**Namespace:** `App\System`  
**Extends:** `BaseSystem\Foundation\Database\BaseModel`  
**Implements:** `OwenIt\Auditing\Contracts\Auditable`

Abstract base class for every domain model in DojoHub. All models under `app/System/*/Models/` extend this class (except `BeltRank` and `User`).

---

## Traits bundled

| Trait | Provides |
|-------|---------|
| `AuditableTrait` (owen-it/laravel-auditing) | Auto-records every create/update/delete to the `audits` table |
| `EditorLogs` (base_system) | Fills `created_by`, `updated_by`, `deleted_by` foreign keys from `Auth::user()` |
| `HasFactory` | Enables `Model::factory()` for tests |
| `HasUlids` | Primary key is a ULID string (`$table->ulid('id')->primary()`) |
| `SoftDeletes` | `deleted_at` column — `delete()` is an UPDATE, not a DELETE |

---

## Hidden fields

These fields are serialized-out (not exposed in JSON/Inertia props) by default:

```php
protected $hidden = [
    'created_by',
    'updated_by',
    'deleted_by',
    'deleted_at',
];
```

The `id`, timestamps, and all user-facing columns are visible by default.

---

## Audit resolution

`AppBaseModel` wires auditing to the `User` model:

```php
public function editorProvider(): ?Authenticatable
{
    return Auth::user();
}

public function getEditorModelClassName(): string
{
    return User::class;
}
```

Morph class is shortened to just the class basename (prevents long namespaced keys in the `audits` table):

```php
public function getMorphClass(): string
{
    return class_basename($this);  // "Venue", not "App\System\Venue\Models\Venue"
}
```

---

## ULID primary keys

`HasUlids` automatically generates ULID strings (26-character, time-sortable, URL-safe) when a model is created. No need to manually set `$id`.

In migrations:

```php
$table->ulid('id')->primary();
```

In code:

```php
$venue->id; // "01HV3X2KQTJ9ZVQFP4A8RYMWX4"
```

---

## Soft-delete behaviour

`SoftDeletes` sets `deleted_at` instead of running a real `DELETE`. Important implication:

> **FK RESTRICT constraints are NOT triggered on soft-delete.** A soft-delete is an `UPDATE` on `deleted_at`, so `ON DELETE RESTRICT` foreign keys do not fire. Enforce referential integrity in application logic (repositories) when needed.

See [database-conventions.md](../../.claude/guidelines/database-conventions.md#soft-delete--fk-restrict).

---

## Conventions for subclasses

Every model that extends `AppBaseModel` should define:

| Property / Method | Required | Notes |
|-------------------|----------|-------|
| `$fillable` | Yes | Explicit mass-assignment whitelist |
| `$sortableColumns` | Yes | Whitelist for `BaseRepository::attachOrderByQuery()` |
| `casts()` | If needed | Return array of casts; use method, not `$casts` property |
| `newFactory()` | Yes | Return the matching `Database\Factories\*Factory` |
| Relationship methods | As needed | Standard Eloquent `belongsTo`, `hasMany`, etc. |

---

## Models that do NOT extend AppBaseModel

| Model | Reason |
|-------|--------|
| `User` | Is the auth model; uses Laravel's `Authenticatable` contracts |
| `BeltRank` | Reference data; no audit, no soft-delete, no editor tracking. Uses `HasUlids` + plain `Model` |

---

## Related

- [database-conventions.md](../../.claude/guidelines/database-conventions.md)
- [architecture.md](../../.claude/guidelines/architecture.md)
- [System Index](Index.md)
