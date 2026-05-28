# Http Layer — Wiki

**Directory**: `app/Http/`

---

## Overview

The Http layer contains Laravel's standard request/response infrastructure: middleware, base controllers, and form requests. Module-level controllers and requests live in `app/Modules/{Module}/Controllers/` and `app/Modules/{Module}/Requests/` respectively — not here. This directory handles cross-cutting concerns: auth flows, settings, and middleware that applies to all requests.

---

## Directory Structure

```
app/Http/
├── Controllers/
│   ├── Auth/
│   │   ├── AuthenticatedSessionController.php   # login / logout
│   │   ├── ConfirmablePasswordController.php    # confirm-password page
│   │   ├── EmailVerificationNotificationController.php
│   │   ├── EmailVerificationPromptController.php
│   │   ├── NewPasswordController.php            # disabled (route commented out)
│   │   ├── PasswordResetLinkController.php      # disabled
│   │   ├── RegisteredUserController.php         # disabled (no public registration)
│   │   └── VerifyEmailController.php
│   ├── Settings/
│   │   ├── PasswordController.php               # settings/password page
│   │   └── ProfileController.php                # settings/profile page
│   └── Controller.php                           # base Laravel Controller
├── Middleware/
│   ├── HandleInertiaRequests.php                # shared Inertia props
│   └── HandleAppearance.php                     # appearance cookie → View::share
└── Requests/
    ├── Auth/
    │   └── LoginRequest.php                     # email + password + rate limiting
    └── Settings/
        └── ProfileUpdateRequest.php             # name validation only
```

---

## Middleware

### Registered in `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    // Cookies that JS can read directly (not encrypted)
    $middleware->encryptCookies(except: [
        'appearance',
        'sidebar_state',
    ]);

    // Appended to the web group (run after all default web middleware)
    $middleware->web(append: [
        HandleAppearance::class,
        HandleInertiaRequests::class,
        AddLinkHeadersForPreloadedAssets::class,
    ]);

    // Spatie Permission middleware aliases
    $middleware->alias([
        'role'               => RoleMiddleware::class,
        'permission'         => PermissionMiddleware::class,
        'role_or_permission' => RoleOrPermissionMiddleware::class,
    ]);
})
```

### Web Middleware Group (full stack, in order)

1. `EncryptCookies` (except `appearance`, `sidebar_state`)
2. `AddQueuedCookiesToResponse`
3. `StartSession`
4. `ShareErrorsFromSession`
5. `VerifyCsrfToken`
6. `SubstituteBindings`
7. **`HandleAppearance`** ← appended
8. **`HandleInertiaRequests`** ← appended
9. **`AddLinkHeadersForPreloadedAssets`** ← appended

### Middleware Alias Reference

| Alias | Class | Usage |
|-------|-------|-------|
| `auth` | `Authenticate` | Requires logged-in user |
| `verified` | `EnsureEmailIsVerified` | Requires verified email |
| `guest` | `RedirectIfAuthenticated` | Redirects if already logged in |
| `throttle` | `ThrottleRequests` | Rate limiting |
| `signed` | `ValidateSignature` | Signed URL validation |
| `role` | Spatie `RoleMiddleware` | `->middleware('role:super_admin')` |
| `permission` | Spatie `PermissionMiddleware` | `->middleware('permission:edit-users')` |
| `role_or_permission` | Spatie `RoleOrPermissionMiddleware` | Either role OR permission |

---

## Controllers

| Controller | Extends | Purpose | Active? |
|-----------|---------|---------|---------|
| `Auth/AuthenticatedSessionController` | `Controller` | Login + logout | ✅ |
| `Auth/ConfirmablePasswordController` | `Controller` | Confirm-password page | ✅ |
| `Auth/EmailVerificationNotificationController` | `Controller` | Resend verification email | ✅ |
| `Auth/EmailVerificationPromptController` | `Controller` | Verify-email prompt page | ✅ |
| `Auth/NewPasswordController` | `Controller` | Reset password form | ⛔ disabled |
| `Auth/PasswordResetLinkController` | `Controller` | Forgot-password form | ⛔ disabled |
| `Auth/RegisteredUserController` | `Controller` | Registration | ⛔ disabled |
| `Auth/VerifyEmailController` | `Controller` | Handle email verification link | ✅ |
| `Settings/ProfileController` | `Controller` | Profile edit page | ✅ |
| `Settings/PasswordController` | `Controller` | Password change page | ✅ |

`Controller.php` is an empty abstract base class (`abstract class Controller {}`). It does not extend `Illuminate\Routing\Controller` or pull in any traits — it is a plain namespace anchor. Module controllers extend `BaseWebController` from `base_system` and are NOT in this directory.

---

## Requests

### `Auth/LoginRequest`

```php
// Validation rules
'email'    => ['required', 'string', 'email']
'password' => ['required', 'string']

// authenticate() method:
// - RateLimiter: 5 attempts/min keyed by email|ip
// - Auth::attempt(['email' => ..., 'password' => ...])
// - throws ValidationException on failure or throttle
```

### `Settings/ProfileUpdateRequest`

```php
'name' => ['required', 'string', 'max:255']
```

Only `name` is validated here. `email` is validated inline inside `ProfileController@update` (not in the Request class).

---

## Child Documentation

- [Middleware/HandleInertiaRequests](Middleware/HandleInertiaRequests.md) — shared props (name, auth, flash, ziggy, sidebarOpen, quote)
- [Middleware/HandleAppearance](Middleware/HandleAppearance.md) — appearance cookie → View::share
- [Controllers/Auth/Index](Controllers/Auth/Index.md) — all auth controller actions
- [Controllers/Settings/Index](Controllers/Settings/Index.md) — profile and password controllers

---

## Related

- [Routes/Index](../Routes/Index.md) — full route listing
- [BaseWebController](../BaseSystem/BaseWebController.md) — base for module controllers
- [Providers/Index](../Providers/Index.md) — middleware registered in bootstrap/app.php
