# Venue Requests

---

## VenueCreateRequest

**File:** `app/Modules/Venues/Requests/VenueCreateRequest.php`  
**Extends:** `BaseWebRequest`

| Field | Rules |
|-------|-------|
| `name` | required, string, max:250 |
| `address_line_1` | required, string, max:250 |
| `address_line_2` | nullable, string, max:250 |
| `city` | required, string, max:100 |
| `state` | nullable, string, max:100 |
| `postal_code` | nullable, string, max:20 |
| `country_code` | required, string, size:2, exists:countries,code |
| `latitude` | nullable, numeric, between:-90,90 |
| `longitude` | nullable, numeric, between:-180,180 |

---

## VenueUpdateRequest

**File:** `app/Modules/Venues/Requests/VenueUpdateRequest.php`

```php
final class VenueUpdateRequest extends VenueCreateRequest {}
```

Identical rules. The `extend` pattern avoids duplication per project convention.

---

## VenueMatRequest

**File:** `app/Modules/Venues/Requests/VenueMatRequest.php`  
**Extends:** `BaseWebRequest`

Used for both mat create (POST) and mat update (PUT). The `mat_number` uniqueness rule self-adapts:

- **Create:** unique per venue, excluding soft-deleted (`whereNull('deleted_at')`)
- **Update:** same, plus `->ignore($this->route('mat'))` to exclude the current mat

| Field | Rules |
|-------|-------|
| `mat_number` | required, integer, min:1, max:99, unique per venue (soft-delete aware) |
| `display_name` | nullable, string, max:50 |

**Unique rule implementation:**

```php
$uniqueRule = Rule::unique('venue_mats', 'mat_number')
    ->where('venue_id', $this->route('venue'))
    ->whereNull('deleted_at');

if ($this->route('mat')) {
    $uniqueRule = $uniqueRule->ignore($this->route('mat'));
}
```

---

## Related

- [VenuesController](VenuesController.md)
- [VenueMatController](VenueMatController.md)
- [forms-and-validation.md](../../../.claude/guidelines/forms-and-validation.md)
