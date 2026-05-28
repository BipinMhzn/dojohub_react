# BaseSystemProvider — Wiki

**File**: `base_system/src/BaseSystemProvider.php`  
**Namespace**: `BaseSystem`  
**Registered in**: `bootstrap/providers.php`

---

## Purpose

`BaseSystemProvider` is the single entry point for the entire base_system package. It auto-discovers and registers every module's `ServiceProvider.php` by scanning `app/Modules/` at boot time. No manual registration is required when adding a new module.

---

## What It Registers

```
BaseSystemProvider
├── SystemRouteProvider       — auto-loads all module Routes/api.php + web.php
├── SystemCommandProvider     — registers base-system:generate Artisan command
└── App\Modules\{X}\ServiceProvider  (for every module directory found)
```

---

## Auto-Discovery Mechanism

```php
protected function discoverModules(): void
{
    $modulePath = $this->app['config']->get('base-system.module');  // 'Modules'
    $directories = File::directories(app_path($modulePath));

    foreach ($directories as $dir) {
        $serviceProviderPath = $dir . '/ServiceProvider.php';
        if (File::exists($serviceProviderPath)) {
            $module = basename($dir);
            $class = "\\App\\{$modulePath}\\{$module}\\ServiceProvider";
            $this->app->register($class);
        }
    }
}
```

**Key behaviour:**
- Scans `app/Modules/` for subdirectories
- For each directory, checks if `ServiceProvider.php` exists
- Registers it via `$this->app->register()`
- Adds no module if `ServiceProvider.php` is missing
- Order of registration = filesystem sort order of module directories

---

## SystemRouteProvider

Also registered by `BaseSystemProvider`. Scans all modules for route files:

```php
// API routes — get `api` middleware group + `/api` prefix
Route::group(['middleware' => 'api', 'prefix' => 'api'], function() {
    require $module->routePath . "/api.php";
});

// Web routes — get `web` middleware group (no prefix)
Route::group(['middleware' => 'web'], function() {
    require $module->routePath . "/web.php";
});
```

**Note:** Each module's `web.php` defines its own prefix (e.g. `Route::prefix('manage/venues')`). The `SystemRouteProvider` does not add any prefix — it only wraps with the `web` middleware group.

---

## Adding a New Module

1. Run `php artisan base-system:generate MyModule --web`
2. The new `app/Modules/MyModules/ServiceProvider.php` is auto-discovered on next request
3. No changes needed to `bootstrap/providers.php` or any config file

---

## Related

- [ModuleGenerateCommand](ModuleGenerateCommand.md) — the generator that creates module scaffolding
- [SystemRouteProvider](BaseSystemProvider.md#systemrouteprovider) — route auto-loading
- [BaseInterface](BaseInterface.md) — interface that module ServiceProviders bind to repositories
