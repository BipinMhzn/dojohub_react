# Users Module

**Directory:** `app/Modules/Users/`  
**Routes prefix:** `/api/users` (JSON API, not Inertia)

**Note:** The Users module is a **JSON API module** (not web/Inertia). It uses `BaseController` (returns `JsonResponse`) rather than `BaseWebController`. The web routes file is empty. This is the only module in DojoHub that is not an Inertia module.

---

## Controller

| Controller | Returns | Route prefix |
|-----------|---------|-------------|
| `UsersController` | `JsonResponse` | `/api/users` (api.php) |

---

## Routes

Routes are defined in `app/Modules/Users/Routes/api.php` (not `web.php`):

```
GET    /api/users          → UsersController@index   (JSON list)
POST   /api/users          → UsersController@store   (JSON create)
GET    /api/users/{id}     → UsersController@show    (JSON single)
PUT    /api/users/{id}     → UsersController@update  (JSON update)
DELETE /api/users/{id}     → UsersController@destroy (JSON delete)
```

---

## Response format

Uses `$this->responseOk($data)` from `BaseController`. Returns standard JSON:

```json
{ "data": { ... } }
```

---

## Service binding

```php
$this->app->bind(UserInterface::class, fn () => new UserRepository(new User));
```

---

## Related

- [UsersController](UsersController.md)
- [System/User](../../System/User.md)
