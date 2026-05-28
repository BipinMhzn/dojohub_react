# ModuleGenerateCommand — Wiki

**File**: `base_system/src/Commands/ModuleGenerateCommand.php`  
**Artisan command**: `php artisan base-system:generate`  
**Registered by**: `SystemCommandProvider`

---

## Purpose

Scaffolds a complete new module from stub templates. In a single command it generates the module directory under `app/Modules/{Plural}/` and the system model under `app/System/{Singular}/`, both populated from stub files with placeholder substitution.

---

## Signature

```bash
php artisan base-system:generate {name} [--web]
```

| Argument/Option | Description |
|----------------|-------------|
| `name` | Module name (singular or plural; command handles both) |
| `--web` | Generate Inertia.js web module instead of API module |

---

## Examples

```bash
# API module
php artisan base-system:generate Tournament

# Inertia web module
php artisan base-system:generate Venue --web
```

---

## What Gets Generated

### API Module (`moduleStubFiles`)

| Stub | Destination | Naming |
|------|------------|--------|
| `api.stub` | `Routes/api.php` | as-is |
| `Controller.stub` | `Controllers/{Plural}Controller.php` | prefixed plural |
| `Filter.stub` | `Filters/{Plural}Filter.php` | prefixed plural |
| `CreateRequest.stub` | `Requests/{Singular}CreateRequest.php` | prefixed singular |
| `Interface.stub` | `Interfaces/{Singular}Interface.php` | prefixed singular |
| `Model.stub` | `Models/{Singular}.php` | singular only |
| `ServiceProvider.stub` | `ServiceProvider.php` | as-is |
| `Repository.stub` | `Repositories/{Singular}Repository.php` | prefixed singular |
| `UpdateRequest.stub` | `Requests/{Singular}UpdateRequest.php` | prefixed singular |
| `web.stub` | `Routes/web.php` | as-is |

### Web Module (`webModuleStubFiles`, `--web`)

| Stub | Destination |
|------|------------|
| `WebController.stub` | `Controllers/{Plural}Controller.php` |
| `Filter.stub` | `Filters/{Plural}Filter.php` |
| `WebCreateRequest.stub` | `Requests/{Singular}CreateRequest.php` |
| `Interface.stub` | `Interfaces/{Singular}Interface.php` |
| `Model.stub` | `Models/{Singular}.php` |
| `ServiceProvider.stub` | `ServiceProvider.php` |
| `Repository.stub` | `Repositories/{Singular}Repository.php` |
| `WebUpdateRequest.stub` | `Requests/{Singular}UpdateRequest.php` |
| `WebRoute.stub` | `Routes/web.php` |

### System Model (`systemStubFiles` — always)

| Stub | Destination |
|------|------------|
| `SystemModel.stub` | `app/System/{Singular}/Models/{Singular}.php` |

---

## Placeholder Substitution

| Placeholder | Replaced with | Example |
|------------|--------------|---------|
| `{{routemodule_singular}}` | Lowercase singular | `venue` |
| `{{routemodule}}` | Lowercase plural | `venues` |
| `{{module}}` | TitleCase plural | `Venues` |
| `{module}` | TitleCase singular | `Venue` |
| `{moduleContainer}` | Module container dir | `Modules` |
| `{system}` | System dir | `System` |

---

## Guard Against Overwriting

If `app/Modules/{Plural}/` already exists, the command exits with:
```
Module Venues already exists!
```

---

## Post-generation Steps

After generating a new module:

1. The `ServiceProvider.php` is auto-discovered — no manual registration needed
2. Add migration in `database/migrations/`
3. Add factory in `database/factories/`
4. Add System model relationships in `app/System/{Name}/Models/{Name}.php`
5. Create frontend pages in `resources/js/pages/{module}/`
6. Add TypeScript types in `resources/js/types/`

---

## Stubs Location

`base_system/src/Stubs/` — edit these to change the default scaffolding for all future modules.

---

## Related

- [BaseSystemProvider](BaseSystemProvider.md) — auto-discovers the generated ServiceProvider
- [SystemRouteProvider](BaseSystemProvider.md) — auto-loads the generated route files
