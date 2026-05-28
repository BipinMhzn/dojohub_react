# EditorLogs Trait — Wiki

**File**: `base_system/src/Foundation/Database/EditorLogs.php`  
**Namespace**: `BaseSystem\Foundation\Database`

---

## Purpose

Eloquent model trait that automatically fills `created_by`, `updated_by`, and `deleted_by` columns on every write operation. Uses Eloquent boot hooks (`bootEditorLogs`) to intercept `creating`, `updating`, and `deleting` events.

---

## Columns Written

| Column | Written on | Value |
|--------|-----------|-------|
| `created_by` | `creating` | `editor->id` |
| `updated_by` | `creating` | `editor->id` |
| `updated_by` | `updating` | `editor->id` |
| `updated_by` | `deleting` | `editor->id` (then `save()`) |
| `deleted_by` | `deleting` | `editor->id` (then `save()`) |

Default column names are overridable via `$createdBy`, `$updatedBy`, `$deletedBy` properties.

---

## Configuration Flags

| Property | Default | Effect |
|----------|---------|--------|
| `static $hasDeleterLog` | `true` | If `false`, skips the `deleting` hook entirely |
| `static $failIfEditorNotFound` | `false` | If `true`, throws `EditorNullException` when no logged-in user |

---

## Abstract Methods

Every model using this trait **must** implement:

```php
abstract public function editorProvider();
abstract public function getEditorModelClassName(): string;
```

**Implementation in `AppBaseModel`:**

```php
public function editorProvider()
{
    return auth()->user();
}

public function getEditorModelClassName(): string
{
    return User::class;
}
```

---

## Boot Flow

```php
static::creating(function ($model) {
    $editor = $self->getEditor();
    if ($editor) {
        $model->created_by = $editor->id;
        $model->updated_by = $editor->id;
    }
});

static::updating(function ($model) {
    $editor = $self->getEditor();
    if ($editor) {
        $model->updated_by = $editor->id;
    }
});

// Only if $hasDeleterLog = true
static::deleting(function ($model) {
    $editor = $self->getEditor();
    if ($editor) {
        $model->updated_by = $editor->id;
        $model->deleted_by = $editor->id;
        $model->save();  // saves before the soft-delete
    }
});
```

---

## Relationships (from trait)

The trait also defines BelongsTo relationships:

```php
public function creator(): BelongsTo   // created_by → users.id
public function updater(): BelongsTo   // updated_by → users.id
public function deleter(): BelongsTo   // deleted_by → users.id
```

---

## Database Column Setup

In migrations, use the `EditorTrait` macro:

```php
// database/traits/EditorTrait.php
$this->editorLogs($table);
```

This adds:
```sql
created_by  BIGINT UNSIGNED NULL
updated_by  BIGINT UNSIGNED NULL
deleted_by  BIGINT UNSIGNED NULL
created_at  TIMESTAMP NULL
updated_at  TIMESTAMP NULL
deleted_at  TIMESTAMP NULL
```

---

## Seeder Behaviour

When running seeders, no user is authenticated, so `editorProvider()` returns `null`. With `$failIfEditorNotFound = false` (default), the columns are simply left null. No exception is thrown.

---

## Related

- [AppBaseModel](../System/AppBaseModel.md) — provides `editorProvider()` + `getEditorModelClassName()`
- [BaseModel](BaseModel.md) — parent class for all models using this trait
- [Database EditorTrait](../Database/EditorTrait.md) — migration macro for adding audit columns
