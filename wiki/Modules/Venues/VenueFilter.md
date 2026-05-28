# VenuesFilter

**File:** `app/Modules/Venues/Filters/VenuesFilter.php`  
**Extends:** `BaseSystem\Filters\BaseFilter`

Applies query-string filters to the venues list query. Each public method becomes an available filter parameter. `BaseFilter::attachFilterQuery()` iterates the validated input and calls the matching method.

---

## Filter methods

### `keyword(string $keyword = ''): void`

Delegates to the `name` filter (defined in `BaseFilter`):

```php
public function keyword(string $keyword = ''): void
{
    $this->name($keyword);
}
```

This means `?keyword=Tokyo` will filter venues whose `name` contains "Tokyo" (case-insensitive `LIKE` search).

---

## BaseWebListRequest filters

Additional filters are handled by `BaseWebListRequest` and applied by `BaseRepository`:

| Parameter | Handled by | Effect |
|-----------|------------|--------|
| `keyword` | `VenuesFilter::keyword()` | Name search |
| `sort_by` | `BaseRepository` | Column whitelist: `$sortableColumns` |
| `sort_order` | `BaseRepository` | `asc` or `desc` |
| `per_page` | `BaseRepository` | Pagination size (default 15) |
| `page` | Laravel paginator | Current page |

---

## Extending the filter

To add a new filter (e.g. `?country_code=NP`):

1. Add a public method to `VenuesFilter`:

```php
public function countryCode(string $countryCode = ''): void
{
    if ($countryCode) {
        $this->builder->whereHas('address', function ($q) use ($countryCode) {
            $q->where('country_code', $countryCode);
        });
    }
}
```

2. Add `country_code` to `VenueListRequest::rules()` (if using a custom list request instead of `BaseWebListRequest`).

---

## Related

- [VenuesController](VenuesController.md)
- [VenueRepository](VenueRepository.md)
- [architecture.md](../../../.claude/guidelines/architecture.md#filter-pattern)
