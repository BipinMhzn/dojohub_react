# PlayerRepository

**File:** `app/Modules/Players/Repositories/PlayerRepository.php`  
**Implements:** `PlayerInterface`  
**Extends:** `BaseRepository`

Simple repository — no multi-table transactions. `create()` and `update()` use the base class defaults since players only write to one table.

---

## `onBeforeResult(Builder $builder): void`

```php
$builder->with('country', 'currentBeltRank');
```

List results include nationality country and current belt rank.

---

## `getById(string $id): Player`

```php
$this->model->newQuery()
    ->with('country', 'currentBeltRank', 'address.country')
    ->findOrFail($id);
```

Single-record query loads more: adds `address.country` for the show page.

---

## Inherited from BaseRepository

| Method | Behaviour |
|--------|-----------|
| `create(array $data)` | `$model->newQuery()->create($data)` |
| `update(string $id, array $data)` | `findOrFail` + `update($data)` |
| `destroy(string $id)` | `findOrFail` + `delete()` (soft-delete) |
| `getList(array $filters)` | Applies filter + order + paginate, calls `onBeforeResult` |

---

## PlayersFilter

**File:** `app/Modules/Players/Filters/PlayersFilter.php`

The default `keyword` method delegates to `name` search (same pattern as VenuesFilter):

```php
public function keyword(string $keyword = ''): void
{
    $this->name($keyword);
}
```

---

## Related

- [PlayersController](PlayersController.md)
- [System/Player](../../System/Player.md)
