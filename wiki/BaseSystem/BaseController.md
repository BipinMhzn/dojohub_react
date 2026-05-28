# BaseController — Wiki

**File**: `base_system/src/Controllers/BaseController.php`  
**Namespace**: `BaseSystem\Controllers`  
**Extends**: `App\Http\Controllers\Controller`  
**Uses**: `SystemResponseTrait`

---

## Purpose

Base controller for JSON API module controllers. Provides HTTP response helpers via `SystemResponseTrait`. Currently used only by the **Users** module, which returns `JsonResponse` instead of Inertia responses.

---

## Class Definition

```php
class BaseController extends Controller
{
    use SystemResponseTrait;
}
```

All functionality comes from `SystemResponseTrait`. The class itself adds no methods.

---

## Usage

```php
class UsersController extends BaseController
{
    public function index(UserListRequest $request): JsonResponse
    {
        $users = $this->repository->getList($request->validated());
        return $this->responseOk($users);
    }
}
```

---

## Available Response Methods

See [SystemResponseTrait](SystemResponseTrait.md) for the full list. Summary:

| Method | HTTP Status | Use case |
|--------|-------------|---------|
| `responseOk($body)` | 200 | Successful data response |
| `responseValidationError($errors)` | 417 | Form validation failure |
| `responseServerError($code)` | 500 | Unexpected server error |
| `responseUnAuthorize($code)` | 401 | Not authenticated |
| `responseForbidden($code)` | 403 | Authenticated but no access |
| `responseNotFound($code)` | 404 | Resource not found |
| `responseBadRequest($code)` | 400 | Bad request parameters |
| `responseConflict($code)` | 409 | Resource conflict |
| `responsePreConditionFailed($code)` | 412 | Precondition check failed |
| `responseExpectationFailed($code)` | 417 | Expectation failed |
| `responseTooManyAttempts($code)` | 429 | Rate limit exceeded |

---

## Contrast: BaseWebController (Inertia)

All web modules use `BaseWebController` which renders Inertia pages. `BaseController` is reserved for modules that expose pure JSON endpoints (e.g., for external consumers or SPA usage).

---

## Related

- [BaseWebController](BaseWebController.md) — Inertia variant
- [SystemResponseTrait](SystemResponseTrait.md) — JSON response helpers
- [SystemConstants](SystemConstants.md) — error code strings used in error responses
- [wiki/Modules/Users/Index.md](../Modules/Users/Index.md) — only module currently using BaseController
