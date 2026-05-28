# SystemConstants — Wiki

**File**: `base_system/src/Constants/SystemConstants.php`  
**Namespace**: `BaseSystem\Constants`

---

## Purpose

Centralised repository of error-code string constants used as `$codeText` arguments to `SystemResponseTrait` response methods. All constants map to translation keys in `base_system/lang/en/base-system.php`.

---

## Error Constants

| Constant | Value | Typical HTTP Status |
|----------|-------|---------------------|
| `ERR_PASSWORD_INVALID_USER` | `err_passwords_user` | 400 |
| `ERR_INVALID_PASSWORD` | `err_invalid_password` | 400 |
| `ERR_PASSWORDS_TOKEN` | `err_passwords_token` | 400 |
| `ERR_PASSWORD_NOT_UPDATED` | `err_password_not_updated` | 400 |
| `ERR_INVALID_CREDENTIAL` | `err_invalid_credentials` | 401 |
| `ERR_EMAIL_DOESNT_EXIST_IN_DATABASE` | `err_email_doesnt_exist_in_system` | 400 |
| `ERR_MAIL_SERVER_AUTH_OR_CONF` | `err_mail_server_auth_or_conf_error` | 500 |
| `ERR_FORM_VALIDATION` | `err_form_validation` | 417 |
| `ERR_OLD_PASSWORD_MISMATCH` | `err_old_password_mismatch` | 400 |
| `ERR_INTERNAL_SERVER_ERROR` | `err_internal_server` | 500 |
| `ERR_UNAUTHORIZED` | `err_unauthorized` | 401 |
| `ERR_FORBIDDEN` | `err_forbidden` | 403 |
| `ERR_NOT_FOUND` | `err_not_found` | 404 |
| `ERR_BAD_REQUEST` | `err_bad_request` | 400 |
| `ERR_METHOD_NOT_ALLOWED` | `err_method_not_allowed` | 405 |
| `ERR_EXPECTATION_FAILED` | `err_expectation_failed` | 417 |
| `ERR_PRECONDITION_FAILED` | `err_precondition_failed` | 412 |
| `ERR_CONFLICT` | `err_conflict` | 409 |
| `ERR_USER_NOT_LOGGED_IN_OR_TOKEN_ABSENT` | `err_user_not_logged_in_or_token_absent` | 401 |
| `ERR_INVALID_OAUTH_CLIENT` | `err_invalid_client` | 401 |
| `ERR_AUTH_TOKEN_ABSENT` | `err_auth_token_absent` | 401 |
| `ERR_INVALID_AUTH_TOKEN` | `err_invalid_auth_token` | 401 |
| `ERR_INVALID_CALLBACK_URL` | `err_invalid_callback_url` | 400 |
| `ERR_TOKEN_EXPIRED` | `err_auth_token_expired` | 401 |
| `ERR_TOO_MANY_LOGIN_ATTEMPT` | `err_too_many_bad_login_attempt` | 429 |
| `ERR_RESOURCE_IN_USE` | `err_resource_in_use` | 409 |
| `ERR_RESOURCE_NOT_FOUND` | `err_resource_not_found` | 404 |
| `ERR_AUTHENTICATION_ERROR` | `err_authentication_error` | 401 |
| `ERR_USER_NOT_REGISTERED` | `err_user_not_registered` | 400 |
| `ERR_PASSWORDS_THROTTLED` | `err_passwords_throttled` | 429 |
| `ERR_EDITOR_NULL` | `err_editor_null` | 500 |

---

## `getAppMsgCodeFromStatusCode(int $code): string`

Static utility that maps an HTTP status code to the corresponding error constant string:

| HTTP Code | Returns |
|-----------|---------|
| 400 | `ERR_BAD_REQUEST` |
| 401 | `ERR_UNAUTHORIZED` |
| 403 | `ERR_FORBIDDEN` |
| 404 | `ERR_NOT_FOUND` |
| 405 | `ERR_METHOD_NOT_ALLOWED` |
| 417 | `ERR_EXPECTATION_FAILED` |
| 412 | `ERR_PRECONDITION_FAILED` |
| anything else | `ERR_INTERNAL_SERVER_ERROR` |

Used in exception handlers to convert HTTP exceptions to error code strings automatically.

---

## Usage

```php
// In a controller
return $this->responseNotFound(SystemConstants::ERR_RESOURCE_NOT_FOUND);

// In an exception handler
$code = SystemConstants::getAppMsgCodeFromStatusCode($exception->getStatusCode());
return $this->baseResponseError($exception->getStatusCode(), $code);
```

---

## Related

- [SystemResponseTrait](SystemResponseTrait.md) — uses these constants as default `$codeText` arguments
