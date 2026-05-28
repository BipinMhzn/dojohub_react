# Providers — Wiki

**Directory**: `app/Providers/` + `base_system/src/`

---

## Overview

DojoHub uses two service providers registered in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    BaseSystem\BaseSystemProvider::class,
];
```

`BaseSystemProvider` is responsible for bootstrapping all module service providers automatically — no per-module entry in `bootstrap/providers.php` is needed.

---

## AppServiceProvider

**File**: `app/Providers/AppServiceProvider.php`

Minimal application provider. Single responsibility: configuring `Password::defaults()`.

```php
public function boot(): void
{
    Password::defaults(function () {
        return Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();
    });
}
```

These rules apply to all password validation using `Password::defaults()` — currently used in `PasswordController::update()`.

> **HaveIBeenPwned check**: `uncompromised()` makes a HIBP API call in production. For local dev this is typically fine but can be disabled by removing `->uncompromised()` during offline development.

---

## BaseSystemProvider

**File**: `base_system/src/BaseSystemProvider.php`

See [BaseSystem/BaseSystemProvider.md](../BaseSystem/BaseSystemProvider.md) for full documentation.

Summary of what it registers:
1. `SystemRouteProvider` — scans `app/Modules/*/Routes/` for `api.php` and `web.php`
2. `SystemCommandProvider` — registers `php artisan base-system:generate`
3. One `ServiceProvider` per discovered module in `app/Modules/`

---

## Module ServiceProviders

Each module has its own `ServiceProvider.php` that binds its interface to its repository:

```php
// app/Modules/Venues/ServiceProvider.php
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VenueInterface::class, VenueRepository::class);
    }
}
```

These are auto-discovered by `BaseSystemProvider` — never listed in `bootstrap/providers.php`.

### All Module ServiceProvider Bindings

| Module | Interface | Repository |
|--------|-----------|-----------|
| BeltRanks | `BeltRankInterface` | `BeltRankRepository` |
| Clubs | `ClubInterface` | `ClubRepository` |
| Coaches | `CoachInterface` | `CoachRepository` |
| Countries | `CountryInterface` | `CountryRepository` |
| Players | `PlayerInterface` | `PlayerRepository` |
| Referees | `RefereeInterface` | `RefereeRepository` |
| TournamentCategories | `TournamentCategoryInterface` | `TournamentCategoryRepository` |
| TournamentRuleSets | `TournamentRuleSetInterface` | `TournamentRuleSetRepository` |
| Tournaments | `TournamentInterface` | `TournamentRepository` |
| Users | `UserInterface` | `UserRepository` |
| Venues | `VenueInterface` | `VenueRepository` |

---

## Provider Load Order

```
bootstrap/providers.php
    ├── AppServiceProvider          (password defaults)
    └── BaseSystemProvider
            ├── SystemRouteProvider (all module routes)
            ├── SystemCommandProvider (artisan commands)
            ├── BeltRanks\ServiceProvider
            ├── Clubs\ServiceProvider
            ├── Coaches\ServiceProvider
            ├── Countries\ServiceProvider
            ├── Players\ServiceProvider
            ├── Referees\ServiceProvider
            ├── TournamentCategories\ServiceProvider
            ├── TournamentRuleSets\ServiceProvider
            ├── Tournaments\ServiceProvider
            ├── Users\ServiceProvider
            └── Venues\ServiceProvider
```

---

## Related

- [BaseSystemProvider](../BaseSystem/BaseSystemProvider.md) — full auto-discovery documentation
- [ModuleGenerateCommand](../BaseSystem/ModuleGenerateCommand.md) — generates ServiceProvider.php stub
