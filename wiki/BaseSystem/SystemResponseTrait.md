# SystemResponseTrait — Wiki

**File**: `base_system/src/Foundation/SystemResponseTrait.php`  
**Namespace**: `BaseSystem\Foundation`

---

## Purpose

Provides standardised JSON response helpers for API controllers and form requests. Used by `BaseController` (API module controllers) and `BaseRequest` (API form requests). Every response wraps data or error codes in a consistent `JsonResponse`.

---

## Response Methods

### Success

#### `responseOk(mixed $body, array $headers = []): JsonResponse`
HTTP 200. Passes the body directly to `response()->json()`. If body is a `Collection`, it is wrapped in a `Paginator`.

```php
return $this->responseOk($users);
return $this->responseOk(['token' => $token, 'user' => $user]);
```

#### `baseResponse(mixed $body, int $status, array $headers): JsonResponse`
Low-level method. Validates the status code (falls back to 500 if invalid), then calls `response()->json($body, $status)`.

---

### Error Responses

All error methods return a `{ message, code }` JSON body. `message` is translated from the lang files; `code` is the string constant.

| Method | HTTP Status | Default `$codeText` |
|--------|-------------|---------------------|
| `responseValidationError($errors)` | **417** | _passes `$errors` array directly_ |
| `responseServerError($codeText)` | 500 | `ERR_INTERNAL_SERVER_ERROR` |
| `responseUnAuthorize($codeText)` | 401 | `ERR_UNAUTHORIZED` |
| `responseForbidden($codeText)` | 403 | `ERR_FORBIDDEN` |
| `responseNotFound($codeText)` | 404 | `ERR_NOT_FOUND` |
| `responseBadRequest($codeText)` | 400 | `ERR_BAD_REQUEST` |
| `responsePreConditionFailed($codeText)` | 412 | `ERR_PRECONDITION_FAILED` |
| `responseConflict($codeText)` | 409 | `ERR_CONFLICT` |
| `responseExpectationFailed($codeText)` | 417 | `ERR_EXPECTATION_FAILED` |
| `responseTooManyAttempts($codeText)` | 429 | `ERR_TOO_MANY_LOGIN_ATTEMPT` |

> **Note:** Validation errors use HTTP **417** (Expectation Failed), not 422. This is an unconventional choice inherited from the base_system design.

---

### `baseResponseError(int $status, string $codeText, array $headers): JsonResponse`

Core error method. Builds:

```json
{
    "message": "Translated error message",
    "code": "err_constant_string"
}
```

Message translation lookup order:
1. `base-system.{codeText}` (package lang file)
2. `{codeText}` (app lang files)
3. `base-system::base-system.{codeText}` (vendor published)
4. Falls back to `$codeText` as-is if no translation found

---

## Error Response Shape

```json
{
    "message": "The resource was not found.",
    "code": "err_not_found"
}
```

Validation errors (`responseValidationError`) pass the raw `$errors` array directly — no `message/code` wrapping:

```json
{
    "name": ["The name field is required."],
    "email": ["The email field must be a valid email."]
}
```

---

## `validateStatusCode(int $code): int`

Checks if a given HTTP code is in the known-valid list. Returns 500 if the code is not recognised. Protects against accidentally sending invalid HTTP status codes.

---

## Translation Files

**`base_system/lang/en/base-system.php`** contains messages for all `ERR_*` constants:

```php
// example entries
'err_not_found' => 'The resource was not found.',
'err_unauthorized' => 'Unauthorized.',
'err_forbidden' => 'Forbidden.',
```

---

## Related

- [SystemConstants](SystemConstants.md) — `ERR_*` string constants used as `$codeText` arguments
- [BaseController](BaseController.md) — uses this trait for API responses
- [BaseRequest](BaseWebRequest.md) — `BaseRequest` (API variant) uses `responseValidationError()` for failed form requests
