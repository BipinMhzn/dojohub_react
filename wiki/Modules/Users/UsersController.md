# UsersController

**File:** `app/Modules/Users/Controllers/UsersController.php`  
**Extends:** `BaseController` (JSON API, NOT `BaseWebController`)  
**Returns:** `JsonResponse`

The only JSON API controller in DojoHub. Returns structured JSON via `$this->responseOk()`.

---

## GET `/api/users` — `index`

**Request:** `BaseListRequest`

Returns paginated user list.

```json
{ "data": { "data": [...], "total": 42, ... } }
```

---

## POST `/api/users` — `store`

**Request:** `UserCreateRequest`

Creates a new user.

**UserCreateRequest rules:**

| Field | Rules |
|-------|-------|
| `name` | required, string, max:255 |
| `email` | required, email, unique:users |
| `password` | required, string, min:8 |

---

## GET `/api/users/{id}` — `show`

**Request:** `BaseRequest`

Returns single user by ID.

---

## PUT `/api/users/{id}` — `update`

**Request:** `UserUpdateRequest`

**UserUpdateRequest rules:**

| Field | Rules |
|-------|-------|
| `name` | sometimes, string, max:255 |
| `email` | sometimes, email, unique:users ignore current |
| `password` | nullable, string, min:8 |

---

## DELETE `/api/users/{id}` — `destroy`

**Request:** `BaseRequest`

Soft-deletes the user.

---

## UserRepository

**File:** `app/Modules/Users/Repositories/UserRepository.php`

Minimal — only overrides `getFilter()`. All operations use base class defaults.

---

## Related

- [Users Index](Index.md)
- [System/User](../../System/User.md)
