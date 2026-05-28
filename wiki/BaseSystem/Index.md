# BaseSystem Package — Wiki

## Overview

`base_system` is a local Composer package (`composer require base-system/base-system`) that provides the foundational scaffolding shared by every module. It lives at `base_system/` in the project root and is path-loaded via `composer.json`.

---

## Package Structure

```
base_system/
├── config/
│   └── base-system.php           # Module + system path config
├── lang/en/
│   └── base-system.php           # Error message translations
└── src/
    ├── BaseSystemProvider.php     # Auto-discovers + registers all module ServiceProviders
    ├── SystemCommandProvider.php  # Registers Artisan commands (ModuleGenerateCommand)
    ├── SystemRouteProvider.php    # Auto-discovers + registers all module route files
    ├── Commands/
    │   └── ModuleGenerateCommand.php  # php artisan base-system:generate
    ├── Constants/
    │   └── SystemConstants.php    # Error code string constants
    ├── Controllers/
    │   ├── BaseController.php     # JSON API controller base
    │   ├── BaseWebController.php  # Inertia web controller base
    │   └── Exceptions/
    │       └── EditorNullException.php
    ├── Filters/
    │   └── BaseFilter.php         # Filter base class
    ├── Foundation/
    │   ├── Database/
    │   │   ├── BaseModel.php      # Eloquent model base (sort, pagination)
    │   │   └── EditorLogs.php     # Trait: auto-fills created_by/updated_by/deleted_by
    │   └── SystemResponseTrait.php # JSON response helpers
    ├── Interfaces/
    │   └── BaseInterface.php      # CRUD contract for all repositories
    ├── Repositories/
    │   └── BaseRepository.php     # Abstract CRUD repository
    ├── Requests/
    │   ├── BaseRequest.php        # JSON API form request
    │   ├── BaseListRequest.php    # JSON API list/filter request
    │   ├── BaseWebRequest.php     # Inertia form request
    │   └── BaseWebListRequest.php # Inertia list/filter request
    └── Stubs/                     # Code generation templates
        ├── Module/                # Module directory scaffold
        └── System/                # System model scaffold
```

---

## Configuration (`base-system.php`)

```php
return [
    'module' => 'Modules',      // app/Modules — where modules live
    'system' => 'System',       # app/System — where system models live
];
```

These values are used by `BaseSystemProvider`, `SystemRouteProvider`, and `ModuleGenerateCommand` to locate modules automatically.

---

## Bootstrap Registration

`bootstrap/providers.php` registers two entries:

```php
return [
    App\Providers\AppServiceProvider::class,
    BaseSystem\BaseSystemProvider::class,      // ← loads everything
];
```

`BaseSystemProvider` in turn registers:
- **`SystemRouteProvider`** — auto-loads all module `Routes/api.php` and `Routes/web.php`
- **`SystemCommandProvider`** — registers `base-system:generate` Artisan command
- **All module `ServiceProvider.php` files** — found by scanning `app/Modules/*/ServiceProvider.php`

---

## Quick Reference

| Component | Class | Purpose |
|-----------|-------|---------|
| [BaseSystemProvider](BaseSystemProvider.md) | `BaseSystem\BaseSystemProvider` | Module auto-discovery |
| [BaseRepository](BaseRepository.md) | `BaseSystem\Repositories\BaseRepository` | CRUD repository base |
| [BaseModel](BaseModel.md) | `BaseSystem\Foundation\Database\BaseModel` | Eloquent model base |
| [EditorLogs](EditorLogs.md) | `BaseSystem\Foundation\Database\EditorLogs` | Audit trail trait |
| [BaseFilter](BaseFilter.md) | `BaseSystem\Filters\BaseFilter` | Filter query base |
| [BaseInterface](BaseInterface.md) | `BaseSystem\Interfaces\BaseInterface` | Repository contract |
| [BaseWebController](BaseWebController.md) | `BaseSystem\Controllers\BaseWebController` | Inertia controller base |
| [BaseController](BaseController.md) | `BaseSystem\Controllers\BaseController` | JSON API controller base |
| [BaseWebRequest](BaseWebRequest.md) | `BaseSystem\Requests\BaseWebRequest` | Inertia form request base |
| [BaseWebListRequest](BaseWebListRequest.md) | `BaseSystem\Requests\BaseWebListRequest` | Inertia list request base |
| [SystemResponseTrait](SystemResponseTrait.md) | `BaseSystem\Foundation\SystemResponseTrait` | JSON response helpers |
| [SystemConstants](SystemConstants.md) | `BaseSystem\Constants\SystemConstants` | Error code constants |
| [ModuleGenerateCommand](ModuleGenerateCommand.md) | `BaseSystem\Commands\ModuleGenerateCommand` | `base-system:generate` |
