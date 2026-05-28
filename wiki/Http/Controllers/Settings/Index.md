# Settings Controllers — Wiki

**Directory**: `app/Http/Controllers/Settings/`  
**Routes file**: `routes/settings.php`  
**Middleware**: `auth` (all routes)

---

## Overview

Two controllers managing authenticated user profile and password settings. Not module-based — they live in the core `app/Http/Controllers/` tree.

---

## Routes

```
GET    /settings              → redirect to /settings/profile
GET    /settings/profile      → profile.edit
PATCH  /settings/profile      → profile.update
GET    /settings/password     → password.edit
PUT    /settings/password     → password.update
GET    /settings/appearance   → appearance (closure → Inertia::render)
```

> `DELETE /settings/profile` (account deletion) is **commented out** — the controller method `destroy()` exists but the route is disabled.

---

## ProfileController

**File**: `app/Http/Controllers/Settings/ProfileController.php`

### `edit(Request $request): Response`

Renders the profile settings page.

```php
return Inertia::render('settings/profile', [
    'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
    'status'          => $request->session()->get('status'),
]);
```

| Prop | Type | Notes |
|------|------|-------|
| `mustVerifyEmail` | `boolean` | Whether the User model requires email verification |
| `status` | `string\|null` | Flash status message (e.g. `'profile-updated'`) |

### `update(ProfileUpdateRequest $request): RedirectResponse`

1. Fills user with validated data (`name`, `email`)
2. If `email` is dirty (changed), clears `email_verified_at`
3. Saves the user
4. Redirects to `profile.edit`

### `destroy(Request $request): RedirectResponse` *(disabled)*

Validates `current_password`, logs out, deletes the user, invalidates session.

---

## ProfileUpdateRequest (`app/Http/Requests/Settings/ProfileUpdateRequest.php`)

| Field | Rules |
|-------|-------|
| `name` | `required`, `string`, `max:255` |
| `email` | `required`, `string`, `lowercase`, `email`, `max:255`, `unique:users,email,{current_user}` |

---

## PasswordController

**File**: `app/Http/Controllers/Settings/PasswordController.php`

### `edit(): Response`

```php
return Inertia::render('settings/password');
```

Renders the password settings page. No props beyond shared props.

### `update(Request $request): RedirectResponse`

Validates inline (no separate request class):

```php
$request->validate([
    'current_password' => ['required', 'current_password'],
    'password'         => ['required', Password::defaults(), 'confirmed'],
]);
```

Then:
1. Hashes new password with `Hash::make()`
2. Updates via `$request->user()->update(['password' => $hashed])`
3. Redirects `back()`

`Password::defaults()` rules (configured in `AppServiceProvider`):
- min: 8
- letters required
- mixed case required
- numbers required
- symbols required
- not compromised (HaveIBeenPwned check)

---

## Appearance Route

The appearance page is a closure route in `routes/settings.php` — no dedicated controller:

```php
Route::get('settings/appearance', function () {
    return Inertia::render('settings/appearance');
})->name('appearance');
```

No props are sent. The appearance state is managed entirely client-side via the `appearance` cookie and `useAppearance` hook.

---

## Inertia Pages

| Route | Inertia Component |
|-------|------------------|
| `GET /settings/profile` | `settings/profile` |
| `GET /settings/password` | `settings/password` |
| `GET /settings/appearance` | `settings/appearance` |

---

## Related

- [Auth Controllers](../Auth/Index.md) — login, logout, email verification
- [Routes](../../Routes/Index.md) — full route listing for settings
