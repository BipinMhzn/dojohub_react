# RefereeRepository

**File:** `app/Modules/Referees/Repositories/RefereeRepository.php`  
**Implements:** `RefereeInterface`  
**Extends:** `BaseRepository`

---

## `onBeforeResult(Builder $builder): void`

```php
$builder->with('country', 'user');
```

---

## `getById(string $id): Referee`

```php
$this->model->newQuery()
    ->with('country', 'user')
    ->findOrFail($id);
```

---

## Inherited operations

All CRUD uses base class: `create()`, `update()`, `destroy()`.

---

## RefereesFilter

**File:** `app/Modules/Referees/Filters/RefereesFilter.php`

Searches `full_name`:

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

- [RefereesController](RefereesController.md)
- [System/Referee](../../System/Referee.md)
