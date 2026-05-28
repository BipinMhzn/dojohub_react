# Auth Controllers — Wiki

**Directory**: `app/Http/Controllers/Auth/`  
**Routes file**: `routes/auth.php`

---

## Overview

Standard Laravel Breeze / Starter Kit authentication controllers. These are NOT module-based — they live in the core `app/Http/Controllers/` tree and use `routes/auth.php` directly (not the `SystemRouteProvider` auto-discovery).

> **Note**: Registration, password reset, and password request flows are **disabled** (commented out in `routes/auth.php`). Only login, email verification, password confirmation, and logout are active.

---

## Active Routes & Controllers

### Login

| Route | Controller | Method | Response |
|-------|-----------|--------|---------|
| `GET /login` | `AuthenticatedSessionController@create` | `create()` | Inertia: `auth/login` |
| `POST /login` | `AuthenticatedSessionController@store` | `store()` | Redirect → `dashboard` |
| `POST /logout` | `AuthenticatedSessionController@destroy` | `destroy()` | Redirect → `/` |

**Middleware**: `guest` (login page) / `auth` (logout)

**`create()` props**:
```php
[
    'canResetPassword' => Route::has('password.request'),  // false — route disabled
    'status'          => $request->session()->get('status'),
]
```

**`store()` flow**:
1. `LoginRequest->authenticate()` — validates credentials, throttles attempts
2. `$request->session()->regenerate()` — prevent session fixation
3. Redirect to intended URL (defaults to `dashboard`)

**`destroy()` flow**:
1. `Auth::guard('web')->logout()`
2. Invalidate + regenerate session token
3. Redirect to `/` → `home` → `login`

---

### Email Verification

| Route | Controller | Method | Middleware |
|-------|-----------|--------|-----------|
| `GET /verify-email` | `EmailVerificationPromptController` | `__invoke()` | `auth` |
| `GET /verify-email/{id}/{hash}` | `VerifyEmailController` | `__invoke()` | `auth`, `signed`, `throttle:6,1` |
| `POST /email/verification-notification` | `EmailVerificationNotificationController@store` | `store()` | `auth`, `throttle:6,1` |

- `EmailVerificationPromptController` — renders `auth/verify-email` page or redirects to `dashboard` if already verified
- `VerifyEmailController` — marks email as verified, redirects to `dashboard?verified=1`
- `EmailVerificationNotificationController` — resends the verification email, redirects back with `status` flash

---

### Password Confirmation

| Route | Controller | Method | Middleware |
|-------|-----------|--------|-----------|
| `GET /confirm-password` | `ConfirmablePasswordController@show` | `show()` | `auth` |
| `POST /confirm-password` | `ConfirmablePasswordController@store` | `store()` | `auth` |

- `show()` renders `auth/confirm-password` Inertia page
- `store()` validates current password, confirms the session, redirects to intended route

---

## Disabled Routes (commented out)

```php
// routes/auth.php — commented
// GET  /register              → RegisteredUserController@create
// POST /register              → RegisteredUserController@store
// GET  /forgot-password       → PasswordResetLinkController@create
// POST /forgot-password       → PasswordResetLinkController@store
// GET  /reset-password/{token}→ NewPasswordController@create
// POST /reset-password        → NewPasswordController@store
```

These controllers exist in the directory but their routes are disabled. Public registration is not available — only the seeded `superuser@dojohub.com` account can log in by default.

---

## LoginRequest (`app/Http/Requests/Auth/LoginRequest.php`)

```php
public function rules(): array
{
    return [
        'email'    => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ];
}
```

`authenticate()` method:
- Uses `RateLimiter` (5 attempts / minute, keyed by `email|ip`)
- Calls `Auth::attempt(['email' => $email, 'password' => $password], $remember)`
- Throws `ValidationException` with `auth.failed` message on failure
- Throws `ValidationException` with `auth.throttle` message on rate limit

---

## Inertia Pages

| Controller | Inertia Component |
|-----------|------------------|
| `AuthenticatedSessionController@create` | `auth/login` |
| `EmailVerificationPromptController` | `auth/verify-email` |
| `ConfirmablePasswordController@show` | `auth/confirm-password` |

---

## Related

- [Routes/Auth](../../Routes/Index.md#auth-routes) — full route listing
- [Settings Controllers](../Settings/Index.md) — profile and password management after login
