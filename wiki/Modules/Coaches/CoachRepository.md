# CoachRepository

**File:** `app/Modules/Coaches/Repositories/CoachRepository.php`  
**Implements:** `CoachInterface`  
**Extends:** `BaseRepository`

---

## `onBeforeResult(Builder $builder): void`

```php
$builder->with('user', 'activeMemberships.club');
```

List results include the linked user account and all current active club memberships.

---

## `getById(string $id): Coach`

```php
$this->model->newQuery()
    ->with('user', 'activeMemberships.club')
    ->findOrFail($id);
```

---

## Inherited operations

| Method | Notes |
|--------|-------|
| `create(array $data)` | Creates coach row directly (no transactions needed) |
| `update(string $id, array $data)` | Updates coach row |
| `destroy(string $id)` | Soft-deletes coach |

---

## CoachesFilter

**File:** `app/Modules/Coaches/Filters/CoachesFilter.php`

Uses `full_name` as the keyword filter target (coaches don't have a `name` column):

```php
public function keyword(string $keyword = ''): void
{
    if ($keyword) {
        $this->builder->where('full_name', 'like', "%{$keyword}%");
    }
}
```

---

## Related

- [CoachesController](CoachesController.md)
- [System/Coach](../../System/Coach.md)
