# BaseWebRequest & BaseWebListRequest — Wiki

**Files**:
- `base_system/src/Requests/BaseWebRequest.php`
- `base_system/src/Requests/BaseWebListRequest.php`

**Namespace**: `BaseSystem\Requests`

---

## BaseWebRequest

Base form request for all Inertia module form requests (create/update operations).

### Class Definition

```php
class BaseWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;  // authorization handled by route middleware (auth, verified)
    }

    public function rules(): array
    {
        return [];    // subclasses override this
    }
}
```

### Key Points

- `authorize()` always returns `true` — no per-request authorization logic
- Authentication is enforced at the route level via `auth` and `verified` middleware
- Validation failures return Laravel's default 422 response with Inertia error sharing
- All module `CreateRequest` and `UpdateRequest` classes extend this

### Usage Pattern

```php
// app/Modules/Venues/Requests/VenueCreateRequest.php
class VenueCreateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:250'],
            'address'   => ['required', 'string', 'max:250'],
            // ...
        ];
    }
}
```

---

## BaseWebListRequest

Base form request for all Inertia list/index endpoints. Validates pagination and sorting parameters.

### Validation Rules

| Field | Rules | Notes |
|-------|-------|-------|
| `page` | `required_if:page,null`, `integer`, `min:1`, `max:99999999` | Page number |
| `sort_by` | `required_if:sort_by,null`, `string`, `max:255` | Column to sort by |
| `sort_order` | `required_if:sort_order,null`, `string`, `in:asc,desc` | Sort direction |
| `per_page` | `required_if:per_page,null`, `integer`, `min:3`, `max:500` | Results per page |
| `keyword` | `nullable`, `string` | Free-text search |

**Note**: `required_if:field,null` means "if the field is literally null, it is required." If absent entirely, the default from `BaseRepository` applies.

### Usage Pattern

```php
// app/Modules/Venues/Requests/VenueListRequest.php
class VenueListRequest extends BaseWebListRequest
{
    // Add extra filter params if needed
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'country' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
```

---

## Contrast: BaseRequest / BaseListRequest (JSON API)

| | `BaseWebRequest` | `BaseRequest` |
|---|---|---|
| For | Inertia modules | JSON API modules |
| Validation failure | 422 + Inertia error bag | 417 JSON via `responseValidationError()` |
| Uses `SystemResponseTrait` | No | Yes |

`BaseRequest` and `BaseListRequest` are the JSON API equivalents, used by the Users module.

---

## Related

- [BaseWebController](BaseWebController.md) — controller that receives these requests
- [BaseFilter](BaseFilter.md) — consumes the validated params from list requests
- [BaseRepository](BaseRepository.md) — `getList()` takes the validated array directly
