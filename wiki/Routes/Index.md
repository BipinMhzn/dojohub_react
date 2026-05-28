# Routes — Wiki

---

## Overview

DojoHub uses four route files in `routes/`:

| File | Purpose | Loaded by |
|------|---------|-----------|
| `routes/web.php` | Core app routes (home, dashboard) | Laravel default |
| `routes/auth.php` | Authentication routes | `web.php` via `require` |
| `routes/settings.php` | Profile/password/appearance | `web.php` via `require` |
| `routes/console.php` | Artisan closure commands | Laravel default |

Module routes are **not** in this directory — they live in `app/Modules/{Module}/Routes/web.php` and are auto-loaded by `SystemRouteProvider`.

---

## `routes/web.php`

```php
Route::get('/', fn () => to_route('login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
```

| Route | Name | Middleware | Response |
|-------|------|-----------|---------|
| `GET /` | `home` | none | Redirect → `login` |
| `GET /dashboard` | `dashboard` | `auth`, `verified` | Inertia: `dashboard` |

---

## `routes/auth.php` — Active Routes

| Method | URI | Controller | Name | Middleware |
|--------|-----|-----------|------|-----------|
| `GET` | `/login` | `AuthenticatedSessionController@create` | `login` | `guest` |
| `POST` | `/login` | `AuthenticatedSessionController@store` | — | `guest` |
| `POST` | `/logout` | `AuthenticatedSessionController@destroy` | `logout` | `auth` |
| `GET` | `/verify-email` | `EmailVerificationPromptController` | `verification.notice` | `auth` |
| `GET` | `/verify-email/{id}/{hash}` | `VerifyEmailController` | `verification.verify` | `auth`, `signed`, `throttle:6,1` |
| `POST` | `/email/verification-notification` | `EmailVerificationNotificationController@store` | `verification.send` | `auth`, `throttle:6,1` |
| `GET` | `/confirm-password` | `ConfirmablePasswordController@show` | `password.confirm` | `auth` |
| `POST` | `/confirm-password` | `ConfirmablePasswordController@store` | — | `auth` |

**Disabled routes** (commented out): register, forgot-password, reset-password.

---

## `routes/settings.php`

| Method | URI | Controller | Name | Middleware |
|--------|-----|-----------|------|-----------|
| `—` | `/settings` | redirect | — | `auth` |
| `GET` | `/settings/profile` | `ProfileController@edit` | `profile.edit` | `auth` |
| `PATCH` | `/settings/profile` | `ProfileController@update` | `profile.update` | `auth` |
| `GET` | `/settings/password` | `PasswordController@edit` | `password.edit` | `auth` |
| `PUT` | `/settings/password` | `PasswordController@update` | `password.update` | `auth` |
| `GET` | `/settings/appearance` | _(closure)_ | `appearance` | `auth` |

> `DELETE /settings/profile` (account deletion) is **commented out**.

---

## `routes/console.php`

```php
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

Single closure command for displaying a random inspiring quote. Not production-relevant.

---

## Module Routes

Each module defines its own routes under `app/Modules/{Module}/Routes/web.php`. These are auto-loaded by `SystemRouteProvider` with the `web` middleware group. Example:

```php
// app/Modules/Venues/Routes/web.php
Route::middleware(['auth', 'verified'])
    ->prefix('manage/venues')
    ->name('venues.')
    ->group(function () {
        Route::get('/', [VenuesController::class, 'index'])->name('index');
        Route::get('/create', [VenuesController::class, 'create'])->name('create');
        // ...
    });
```

### Module Route Map

| Module | URL Prefix | Route Name Prefix |
|--------|-----------|------------------|
| Venues | `/manage/venues` | `venues.` |
| Clubs | `/manage/clubs` | `clubs.` |
| Players | `/manage/players` | `players.` |
| Coaches | `/manage/coaches` | `coaches.` |
| Referees | `/manage/referees` | `referees.` |
| Tournaments | `/manage/tournaments` | `tournaments.` |
| Users | `/api/users` | `users.` (JSON API) |
| BeltRanks | `/manage/belt-ranks` | `belt-ranks.` |
| TournamentRuleSets | `/manage/tournament-rule-sets` | `tournament-rule-sets.` |
| Countries | `/api/countries` | `countries.` (JSON API) |
| TournamentCategories | nested under tournaments | `tournament-categories.` |

See individual [Modules](../Modules/) documentation for exact routes per module.

---

## Route Middleware Groups

| Group | Applied by | Middleware in group |
|-------|-----------|-------------------|
| `web` | Laravel kernel | `EncryptCookies`, `AddQueuedCookies`, `StartSession`, `ShareErrorsFromSession`, `VerifyCsrfToken`, `SubstituteBindings`, `HandleAppearance`, `HandleInertiaRequests`, `AddLinkHeadersForPreloadedAssets` |
| `api` | Laravel kernel | `ThrottleRequests:api`, `SubstituteBindings` |

**Custom middleware aliases** (registered in `bootstrap/app.php`):

| Alias | Class |
|-------|-------|
| `role` | `Spatie\Permission\Middleware\RoleMiddleware` |
| `permission` | `Spatie\Permission\Middleware\PermissionMiddleware` |
| `role_or_permission` | `Spatie\Permission\Middleware\RoleOrPermissionMiddleware` |

---

## Related

- [Auth Controllers](../Http/Controllers/Auth/Index.md)
- [Settings Controllers](../Http/Controllers/Settings/Index.md)
- [HandleInertiaRequests](../Http/Middleware/HandleInertiaRequests.md)
- [BaseSystemProvider](../BaseSystem/BaseSystemProvider.md) — SystemRouteProvider auto-loads module routes
