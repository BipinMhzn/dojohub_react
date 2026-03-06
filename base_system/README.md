# BaseSystem — Developer Wiki

A modular scaffolding and architecture layer built on top of Laravel. It enforces consistent patterns across the application by providing base classes, automatic module discovery, a code generator, standardised API responses, and Inertia.js-native web module generation.

---

## Table of Contents

1. [Overview](#overview)
2. [Directory Structure](#directory-structure)
3. [Setup & Installation](#setup--installation)
4. [Core Components](#core-components)
   - [BaseSystemProvider](#basesystemprovider)
   - [SystemRouteProvider](#systemrouteprovider)
   - [SystemCommandProvider](#systemcommandprovider)
   - [BaseModel](#basemodel)
   - [EditorLogs Trait](#editorlogs-trait)
   - [BaseRepository](#baserepository)
   - [BaseInterface](#baseinterface)
   - [BaseController](#basecontroller)
   - [BaseWebController](#basewebcontroller)
   - [SystemResponseTrait](#systemresponsetrait)
   - [BaseFilter](#basefilter)
   - [BaseRequest & BaseListRequest](#baserequest--baselistrequest)
   - [BaseWebRequest & BaseWebListRequest](#basewebrequest--baseweblistrequest)
   - [SystemConstants](#systemconstants)
5. [Code Generator](#code-generator)
   - [API Module (default)](#api-module-default)
   - [Web Module (Inertia.js)](#web-module-inertiajs)
6. [Generated Module Structure](#generated-module-structure)
7. [Data Flow](#data-flow)
8. [Configuration](#configuration)
9. [Language / Translations](#language--translations)
10. [Stubs Reference](#stubs-reference)
11. [Suggested Improvements](#suggested-improvements)

---

## Overview

BaseSystem is a **local Laravel package** (loaded via Composer's `path` repository) that provides:

- **Base classes** — Model, Repository, Controller, Request, Filter; plus a dedicated **web (Inertia.js) layer**
- **Audit logging** — `EditorLogs` trait tracks `created_by`, `updated_by`, `deleted_by`
- **Standardised JSON responses** — `SystemResponseTrait` with 13+ response helpers (API layer)
- **Inertia.js-native responses** — `BaseWebController` wraps `Inertia::render()` for server-driven SPAs
- **Module auto-discovery** — ServiceProviders and Routes registered automatically
- **Code generation** — `php artisan base-system:generate {Name}` scaffolds a full API module; add `--web` for an Inertia module
- **Centralised error codes** — `SystemConstants` + language files for 40+ error codes

---

## Directory Structure

```
base_system/
├── composer.json                     # Package definition (namespace: BaseSystem)
├── config/
│   └── base-system.php               # module & system directory config
├── lang/
│   └── en/
│       └── base-system.php           # 40+ translated error messages
└── src/
    ├── BaseSystemProvider.php        # Main service provider
    ├── SystemCommandProvider.php     # Registers Artisan commands
    ├── SystemRouteProvider.php       # Auto-discovers module routes
    ├── Commands/
    │   └── ModuleGenerateCommand.php # php artisan base-system:generate [--web]
    ├── Constants/
    │   └── SystemConstants.php       # Error code constants
    ├── Controllers/
    │   ├── BaseController.php        # API base (uses SystemResponseTrait)
    │   ├── BaseWebController.php     # Web base (wraps Inertia::render)
    │   └── Exceptions/
    │       └── EditorNullException.php
    ├── Filters/
    │   └── BaseFilter.php
    ├── Foundation/
    │   ├── SystemResponseTrait.php
    │   └── Database/
    │       ├── BaseModel.php
    │       └── EditorLogs.php        # Audit trail trait
    ├── Interfaces/
    │   └── BaseInterface.php
    ├── Repositories/
    │   └── BaseRepository.php
    ├── Requests/
    │   ├── BaseRequest.php           # API request (returns JSON 417 on failure)
    │   ├── BaseListRequest.php       # API list request (required_if rules)
    │   ├── BaseWebRequest.php        # Web request (Inertia redirect-back on failure)
    │   └── BaseWebListRequest.php    # Web list request (all-nullable rules)
    └── Stubs/                        # Code generation templates
        ├── Module/                   # Scaffold directory placeholders
        │   ├── Controllers/
        │   ├── Database/Models/
        │   ├── Exceptions/
        │   ├── Filters/
        │   ├── Interfaces/
        │   ├── Repositories/
        │   ├── Requests/
        │   ├── Routes/
        │   └── Services/
        ├── System/                   # System model scaffold placeholder
        │   └── Database/Models/
        ├── Controller.stub           # API controller
        ├── WebController.stub        # Inertia controller
        ├── Repository.stub
        ├── Model.stub
        ├── SystemModel.stub
        ├── Filter.stub
        ├── CreateRequest.stub        # API request
        ├── WebCreateRequest.stub     # Inertia request
        ├── UpdateRequest.stub        # API request
        ├── WebUpdateRequest.stub     # Inertia request (extends CreateRequest)
        ├── Interface.stub
        ├── ServiceProvider.stub
        ├── api.stub                  # API routes
        ├── web.stub                  # Empty web routes placeholder
        └── WebRoute.stub             # Inertia routes (auth+verified, manage prefix)
```

---

## Setup & Installation

### 1. Register the Package

Add to your Laravel app's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./base_system"
        }
    ],
    "require": {
        "bipin/base-system": "*"
    }
}
```

Run `composer install`.

### 2. Register the Service Provider

In `config/app.php` (or via `bootstrap/providers.php` in Laravel 11+):

```php
'providers' => [
    // ...
    BaseSystem\BaseSystemProvider::class,
],
```

### 3. Publish Config

```bash
php artisan vendor:publish --tag=base-system
```

This publishes:
- `config/base-system.php` — configure module & system paths
- `lang/en/base-system.php` — error message translations

---

## Core Components

### BaseSystemProvider

**File:** `src/BaseSystemProvider.php`

The root service provider. On `register()` it:

1. Merges `config/base-system.php` defaults
2. Scans `app/{module}/` for module `ServiceProvider.php` or files inside `Providers/` subdirectories and registers them automatically
3. Registers `SystemRouteProvider`
4. Registers `SystemCommandProvider` (console only)

On `boot()` it loads translations and publishes config/lang assets.

**Auto-discovery logic:**

```
app/Modules/
├── Players/
│   └── ServiceProvider.php          ← auto-registered
├── Tournaments/
│   └── Providers/
│       └── TournamentProvider.php   ← auto-registered
```

---

### SystemRouteProvider

**File:** `src/SystemRouteProvider.php`

Extends Laravel's `RouteServiceProvider`. Scans every module for a `Routes/` folder and registers:

- `web.php` → wrapped in the `web` middleware group
- `api.php` → wrapped in the `api` middleware group with `/api` prefix

No manual route registration is needed when adding a new module.

---

### SystemCommandProvider

**File:** `src/SystemCommandProvider.php`

Registers `ModuleGenerateCommand` (only when running in console context). This keeps it out of the HTTP request lifecycle.

---

### BaseModel

**File:** `src/Foundation/Database/BaseModel.php`

Abstract base for all Eloquent models in the system.

| Property | Type | Default | Purpose |
|---|---|---|---|
| `$defaultSortColumn` | `string` | `"name"` | Fallback sort column |
| `$defaultSortOrder` | `string` | `"asc"` | Fallback sort direction |
| `$sortableColumns` | `array` | `[]` | Whitelist of sortable columns |
| `$sortKeyMaps` | `array` | `[]` | Maps client key → DB column |
| `$perPage` | `int` | `10` | Default pagination size |

**Key methods:**

```php
$model->sortableColumns();           // Returns the whitelist array
$model->defaultSortColumn();         // Returns default sort column
$model->defaultSortOrder();          // Returns 'asc' or 'desc'
$model->mapSortKey('full_name');     // Maps to DB column via sortKeyMaps
```

**Usage in a module model:**

```php
class Player extends BaseModel
{
    use EditorLogs;

    protected string $defaultSortColumn = 'last_name';
    protected array $sortableColumns = ['first_name', 'last_name', 'weight'];
    protected array $sortKeyMaps = ['name' => 'last_name'];
    protected $perPage = 15;
}
```

---

### EditorLogs Trait

**File:** `src/Foundation/Database/EditorLogs.php`

Provides automatic audit trail by hooking into Eloquent model events.

**Tracked columns:**

| Column | Set on |
|---|---|
| `created_by` | `creating` event |
| `updated_by` | `creating`, `updating`, `deleting` events |
| `deleted_by` | `deleting` event (if `$hasDeleterLog = true`) |

**Configuration flags:**

```php
protected static bool $hasDeleterLog = true;       // Track deleted_by
protected static bool $failIfEditorNotFound = false; // Throw if no auth user
```

**Required implementations (abstract):**

```php
// Who is performing the action?
public function editorProvider()
{
    return Auth::user();
}

// Which model class represents the editor?
public function getEditorModelClassName(): string
{
    return User::class;
}
```

**Relationships available after using the trait:**

```php
$player->creator;   // User who created the record
$player->updater;   // User who last updated the record
$player->deleter;   // User who deleted the record
```

**Required migration columns:**

```php
$table->string('created_by')->nullable();
$table->string('updated_by')->nullable();
$table->string('deleted_by')->nullable(); // If hasDeleterLog = true
```

---

### BaseRepository

**File:** `src/Repositories/BaseRepository.php`

Abstract data access layer. Implements `BaseInterface`.

**Constructor:** Requires an injected `BaseModel` instance.

**Methods:**

```php
// Paginated or full list with sorting + filtering
getList(array $params = [], bool $paginate = true, array $attributes = [])

// Create a model
create(array $attributes): BaseModel

// Update by ID (throws ModelNotFoundException if not found)
update(string $id, array $attributes): BaseModel

// Delete by ID
destroy(string $id, array $attributes = []): true

// Find by ID (throws ModelNotFoundException if not found)
getById(string $id, array $attributes = []): BaseModel
```

**Accepted `$params` keys for `getList`:**

| Key | Description | Default |
|---|---|---|
| `sort_by` | Column to sort by | `defaultSortColumn` |
| `sort_order` | `asc` or `desc` | `defaultSortOrder` |
| `per_page` | Items per page | Model's `$perPage` |
| `page` | Current page | 1 |
| `all` | Set to `1` to disable pagination | — |

**Hook — `onBeforeResult`:**

Override this in your repository to modify the query builder before results are fetched:

```php
public function onBeforeResult(Builder $builder): void
{
    $builder->with('club')->whereNull('archived_at');
}
```

**Abstract method — `getFilter`:**

Must return a filter instance:

```php
public function getFilter(Builder $builder): PlayerFilter
{
    return new PlayerFilter($builder);
}
```

**Custom lifecycle events:**

Register event class names in `$events` to dispatch them automatically:

```php
protected array $events = [
    'creating' => PlayerCreatingEvent::class,
    'created'  => PlayerCreatedEvent::class,
    'updating' => PlayerUpdatingEvent::class,
    'updated'  => PlayerUpdatedEvent::class,
    'deleting' => PlayerDeletingEvent::class,
    'deleted'  => PlayerDeletedEvent::class,
];
```

---

### BaseInterface

**File:** `src/Interfaces/BaseInterface.php`

Contract that all repositories must satisfy:

```php
interface BaseInterface
{
    public function create(array $attributes): BaseModel;
    public function update(string $id, array $attributes): BaseModel;
    public function getById(string $id, array $attributes = []): BaseModel;
    public function getList(array $params = [], bool $paginate = true, array $attributes = []): LengthAwarePaginator|Collection;
    public function destroy(string $id, array $attributes = []): true;
}
```

Bind the interface to the repository in your module's `ServiceProvider`:

```php
$this->app->bind(PlayerInterface::class, PlayerRepository::class);
```

---

### BaseController

**File:** `src/Controllers/BaseController.php`

Thin base controller that uses `SystemResponseTrait`. Inject the repository interface via constructor:

```php
class PlayerController extends BaseController
{
    public function __construct(protected PlayerInterface $repository) {}

    public function index(BaseListRequest $request): JsonResponse
    {
        return $this->responseOk($this->repository->getList($request->validated()));
    }
}
```

---

### BaseWebController

**File:** `src/Controllers/BaseWebController.php`

Web counterpart to `BaseController`. Extends Laravel's `Controller` directly and wraps `Inertia::render()` in a protected `render()` helper, keeping controllers clean and decoupled from the Inertia facade.

```php
abstract class BaseWebController extends Controller
{
    protected function render(string $component, array $props = []): Response
    {
        return Inertia::render($component, $props);
    }
}
```

**Usage in a module controller:**

```php
class ClubsController extends BaseWebController
{
    public function __construct(private readonly ClubInterface $repository) {}

    public function index(BaseWebListRequest $request): Response
    {
        return $this->render('clubs/index', [
            'clubs'   => $this->repository->getList($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function store(ClubCreateRequest $request): RedirectResponse
    {
        $this->repository->create($request->validated());

        return to_route('clubs.index')->with('success', 'Club created successfully.');
    }
}
```

> **Note:** Do not mix `BaseWebController` with `SystemResponseTrait`. Web modules use Inertia redirects for errors, not JSON.

---

### SystemResponseTrait

**File:** `src/Foundation/SystemResponseTrait.php`

Provides standardised JSON response helpers. All methods accept an optional `$headers` array.

| Method | HTTP Status | Use Case |
|---|---|---|
| `responseOk($body)` | 200 | Successful data response |
| `responseValidationError($errors)` | 417 | Form validation failures |
| `responseServerError($code)` | 500 | Unexpected server errors |
| `responseUnAuthorize($code)` | 401 | Authentication required |
| `responseForbidden($code)` | 403 | Permission denied |
| `responseNotFound($code)` | 404 | Resource not found |
| `responseBadRequest($code)` | 400 | Invalid request |
| `responseConflict($code)` | 409 | State conflict (e.g. duplicate) |
| `responsePreConditionFailed($code)` | 412 | Pre-condition not met |
| `responseExpectationFailed($code)` | 417 | Expectation failed |
| `responseTooManyAttempts($code)` | 429 | Rate limit exceeded |
| `baseResponse($body, $status)` | custom | Flexible low-level response |
| `baseResponseError($status, $code)` | custom | Flexible error response |

**Error response format:**

```json
{
    "message": "The resource you requested was not found.",
    "code": "err_not_found"
}
```

Error codes are automatically translated via `lang/en/base-system.php`.

---

### BaseFilter

**File:** `src/Filters/BaseFilter.php`

Magic-method based filter system. Any public method on a filter class becomes a filter that can be triggered by a matching query parameter name.

**Built-in filters:**

```php
title(string $title)   // WHERE title LIKE '%...%'
name(string $name)     // WHERE name LIKE '%...%'
```

**Abstract — must implement:**

```php
abstract public function keyword(string $keyword): void;
```

**Example custom filter:**

```php
class PlayerFilter extends BaseFilter
{
    public function keyword(string $keyword): void
    {
        $this->builder->where(function ($q) use ($keyword) {
            $q->where('first_name', 'like', "%{$keyword}%")
              ->orWhere('last_name', 'like', "%{$keyword}%");
        });
    }

    public function gender(string $gender): void
    {
        $this->builder->where('gender', $gender);
    }

    public function club_id(string $clubId): void
    {
        $this->builder->where('club_id', $clubId);
    }
}
```

Passing `?keyword=john&gender=male` in the request will call both `keyword()` and `gender()` automatically.

> **Note:** Empty-string values are silently skipped (`array_filter` with `strlen`). Falsy-but-valid values like `"0"` are also skipped.

---

### BaseRequest & BaseListRequest

**File:** `src/Requests/BaseRequest.php` and `BaseListRequest.php`

`BaseRequest` overrides Laravel's `failedValidation` to return a JSON response (HTTP 417) instead of a redirect. Used by **API modules only**.

`BaseListRequest` validates common list parameters:

```php
'page'       => 'integer|min:1|max:99999999'
'sort_by'    => 'string|max:255'
'sort_order' => 'in:asc,desc'
'per_page'   => 'integer|min:3|max:500'
'keyword'    => 'nullable|string'
```

---

### BaseWebRequest & BaseWebListRequest

**File:** `src/Requests/BaseWebRequest.php` and `BaseWebListRequest.php`

Web equivalents of `BaseRequest` / `BaseListRequest` for **Inertia.js modules**.

`BaseWebRequest` is a plain `FormRequest` with **no `response()` override**. When validation fails, Laravel's default behaviour redirects back with errors in the session — which is exactly what Inertia's server-side middleware expects to surface errors on the client.

```php
abstract class BaseWebRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return []; }
    // No response() override — Inertia handles redirect-back with errors automatically
}
```

`BaseWebListRequest` extends `BaseWebRequest` with all rules set to `nullable` (rather than `required_if`). Missing parameters fall back to repository defaults without triggering validation errors.

```php
'page'       => ['nullable', 'integer', 'min:1']
'sort_by'    => ['nullable', 'string', 'max:255']
'sort_order' => ['nullable', 'string', 'in:asc,desc']
'per_page'   => ['nullable', 'integer', 'min:3', 'max:500']
'keyword'    => ['nullable', 'string']
```

**When to use which:**

| Scenario | Request base class |
|---|---|
| JSON API endpoint | `BaseRequest` / `BaseListRequest` |
| Inertia.js web page | `BaseWebRequest` / `BaseWebListRequest` |

---

### SystemConstants

**File:** `src/Constants/SystemConstants.php`

Centralised string constants for all error codes. A selection:

```php
ERR_UNAUTHORIZED               // 401
ERR_FORBIDDEN                  // 403
ERR_NOT_FOUND                  // 404
ERR_BAD_REQUEST                // 400
ERR_FORM_VALIDATION            // 417
ERR_CONFLICT                   // 409
ERR_INTERNAL_SERVER_ERROR      // 500
ERR_INVALID_PASSWORD
ERR_EDITOR_NULL
ERR_TOO_MANY_LOGIN_ATTEMPT
// ... 30+ more
```

Also provides a helper:

```php
SystemConstants::getAppMsgCodeFromStatusCode(404); // Returns ERR_NOT_FOUND
```

---

## Code Generator

```bash
php artisan base-system:generate {ModuleName} [--web]
```

Scaffolds a complete module from stubs. Module name can be singular or plural — it handles both automatically.

---

### API Module (default)

Generates a JSON API module using `BaseController` and `SystemResponseTrait`.

```bash
php artisan base-system:generate Tournament
# Creates app/Modules/Tournaments/ and app/System/Tournament/
```

**What gets created:**

| File/Dir | Location | Notes |
|---|---|---|
| `TournamentsController.php` | `Modules/Tournaments/Controllers/` | Extends `BaseController` |
| `TournamentRepository.php` | `Modules/Tournaments/Repositories/` | |
| `TournamentInterface.php` | `Modules/Tournaments/Interfaces/` | |
| `TournamentsFilter.php` | `Modules/Tournaments/Filters/` | |
| `Tournament.php` | `Modules/Tournaments/Database/Models/` | Module model (empty extension point) |
| `TournamentCreateRequest.php` | `Modules/Tournaments/Requests/` | Extends `BaseRequest` |
| `TournamentUpdateRequest.php` | `Modules/Tournaments/Requests/` | Extends `BaseRequest` |
| `Exceptions/` | `Modules/Tournaments/` | Empty — populate when needed |
| `Services/` | `Modules/Tournaments/` | Empty — add service classes here |
| `ServiceProvider.php` | `Modules/Tournaments/` | |
| `api.php` | `Modules/Tournaments/Routes/` | `Route::apiResource(...)` |
| `web.php` | `Modules/Tournaments/Routes/` | Empty placeholder |
| `Tournament.php` | `System/Tournament/Database/Models/` | System model (canonical definition) |

---

### Web Module (Inertia.js)

Generates an Inertia.js module using `BaseWebController`, `BaseWebRequest`, and a fully wired web route file.

```bash
php artisan base-system:generate Club --web
# Creates app/Modules/Clubs/ and app/System/Club/
```

**What gets created:**

| File | Location |
|---|---|
| File/Dir | Location | Notes |
|---|---|---|
| `ClubsController.php` | `Modules/Clubs/Controllers/` | Extends `BaseWebController` |
| `ClubRepository.php` | `Modules/Clubs/Repositories/` | |
| `ClubInterface.php` | `Modules/Clubs/Interfaces/` | |
| `ClubsFilter.php` | `Modules/Clubs/Filters/` | |
| `Club.php` | `Modules/Clubs/Database/Models/` | Module model (empty extension point) |
| `ClubCreateRequest.php` | `Modules/Clubs/Requests/` | Extends `BaseWebRequest` |
| `ClubUpdateRequest.php` | `Modules/Clubs/Requests/` | Extends `ClubCreateRequest` |
| `Exceptions/` | `Modules/Clubs/` | Empty — populate when needed |
| `Services/` | `Modules/Clubs/` | Empty — add service classes here |
| `ServiceProvider.php` | `Modules/Clubs/` | |
| `web.php` | `Modules/Clubs/Routes/` | auth+verified, manage prefix, POST-for-update |
| `Club.php` | `System/Club/Database/Models/` | System model (canonical definition) |

**Controller differences vs API:**

| | API module | Web module |
|---|---|---|
| Base class | `BaseController` | `BaseWebController` |
| Response type | `JsonResponse` | `Inertia\Response` / `RedirectResponse` |
| Request base | `BaseRequest` / `BaseListRequest` | `BaseWebRequest` / `BaseWebListRequest` |
| Update route | `PUT/PATCH` | `POST` (HTML form workaround) |
| Validation errors | JSON 417 | Redirect back with session errors |

**Generated route file (`web.php`):**

```php
Route::group(['middleware' => ['auth', 'verified'], 'prefix' => 'manage'], function () {
    Route::resource('clubs', ClubsController::class)
        ->except('update')
        ->names('clubs');
    Route::post('clubs/{club}', [ClubsController::class, 'update'])
        ->name('clubs.update');
});
```

The POST-for-update pattern exists because standard HTML forms only support `GET` and `POST`. Inertia's `router.put()` / `router.patch()` handles this transparently on the frontend by sending a `_method` field, but generating a dedicated POST route keeps the backend explicit.

---

## Generated Module Structure

### API module (`base-system:generate Tournament`)

```
app/
├── Modules/
│   └── Tournaments/
│       ├── Controllers/
│       │   └── TournamentsController.php  # Extends BaseController, returns JsonResponse
│       ├── Database/
│       │   └── Models/
│       │       └── Tournament.php         # Extends System Tournament model (empty or extended)
│       ├── Exceptions/                    # Module-specific exceptions
│       ├── Filters/
│       │   └── TournamentsFilter.php
│       ├── Interfaces/
│       │   └── TournamentInterface.php
│       ├── Repositories/
│       │   └── TournamentRepository.php
│       ├── Requests/
│       │   ├── TournamentCreateRequest.php
│       │   └── TournamentUpdateRequest.php
│       ├── Routes/
│       │   ├── api.php                    # Route::apiResource(...)
│       │   └── web.php                    # Empty placeholder
│       ├── Services/                      # Business logic / service classes
│       └── ServiceProvider.php
└── System/
    └── Tournament/
        └── Database/
            └── Models/
                └── Tournament.php         # Canonical schema: fillable, casts, relationships
```

### Web module (`base-system:generate Club --web`)

```
app/
├── Modules/
│   └── Clubs/
│       ├── Controllers/
│       │   └── ClubsController.php        # Extends BaseWebController, returns Inertia\Response
│       ├── Database/
│       │   └── Models/
│       │       └── Club.php               # Extends System Club model (empty or extended)
│       ├── Exceptions/
│       ├── Filters/
│       │   └── ClubsFilter.php
│       ├── Interfaces/
│       │   └── ClubInterface.php
│       ├── Repositories/
│       │   └── ClubRepository.php
│       ├── Requests/
│       │   ├── ClubCreateRequest.php      # Extends BaseWebRequest
│       │   └── ClubUpdateRequest.php      # Extends ClubCreateRequest
│       ├── Routes/
│       │   └── web.php                    # auth+verified, manage prefix, POST-for-update
│       ├── Services/
│       └── ServiceProvider.php
└── System/
    └── Club/
        └── Database/
            └── Models/
                └── Club.php               # Canonical schema: fillable, casts, relationships
```

**Two-level model design:**

| Layer | Location | Purpose |
|---|---|---|
| System model | `app/System/{Entity}/Database/Models/` | Canonical definition — fillable, casts, relationships, factory |
| Module model | `app/Modules/{Entities}/Database/Models/` | Module-specific extension — override scopes, add local relationships, mutators |

The module model starts empty and only grows when the module needs to deviate from the base definition. Most of the time it stays empty — which is intentional. It provides the extension point without forcing you to edit the shared system model.

---

## Data Flow

### API module

```
HTTP Request
    │
    ▼
Module Controller (extends BaseController)
    │  uses SystemResponseTrait for JSON responses
    │
    ▼
Module Interface (contract)
    │  injected via ServiceProvider binding
    │
    ▼
Module Repository (extends BaseRepository)
    │
    ├── attachOrderByQuery()       ← Applies sorting from request params
    ├── getFilter()                ← Returns module's Filter instance
    ├── filter.attachFilterQuery() ← Applies dynamic filters
    ├── onBeforeResult()           ← Optional query customisation hook
    │
    ▼
Eloquent Builder → Database
    │
    ▼
BaseModel (with EditorLogs trait)
    │  Auto-sets created_by / updated_by / deleted_by
    │
    ▼
JSON Response via SystemResponseTrait
```

### Web (Inertia) module

```
HTTP Request
    │
    ▼
Module Controller (extends BaseWebController)
    │  $this->render('page/component', $props)
    │
    ▼
Module Interface (contract)
    │  injected via ServiceProvider binding
    │
    ▼
Module Repository (extends BaseRepository)
    │
    ├── attachOrderByQuery()       ← Applies sorting from request params
    ├── getFilter()                ← Returns module's Filter instance
    ├── filter.attachFilterQuery() ← Applies dynamic filters
    ├── onBeforeResult()           ← Optional query customisation hook
    │
    ▼
Eloquent Builder → Database
    │
    ▼
BaseModel (with EditorLogs trait)
    │  Auto-sets created_by / updated_by / deleted_by
    │
    ▼
Inertia::render() → React page component (props injected as component props)
    │
    ▼  (on mutation: store / update / destroy)
to_route()->with('success', '...')  ← Inertia intercepts redirect, updates page
```

---

## Configuration

**`config/base-system.php`**

```php
return [
    'module' => 'Modules',   // Relative to app/ — where module classes live
    'system' => 'System',    // Relative to app/ — where base system models live
];
```

To change module paths, publish the config and update these values. The provider, route discoverer, and code generator all read from this config.

---

## Language / Translations

**`lang/en/base-system.php`**

Maps error code constants to human-readable English strings. Example:

```php
return [
    'err_unauthorized'        => 'Unauthorized access.',
    'err_not_found'           => 'The resource you requested was not found.',
    'err_invalid_password'    => "Invalid password or password doesn't match.",
    'err_form_validation'     => 'Form validation failed.',
    // ...
];
```

`SystemResponseTrait::translateCodeText()` attempts three lookup strategies before falling back to the raw code string, so you can override messages in your app's own language files.

To add a language:

```bash
cp lang/en/base-system.php lang/fr/base-system.php
# Translate the values
```

---

## Stubs Reference

All stubs live in `src/Stubs/`. Supported placeholder variables:

| Placeholder | Replaced with | Example |
|---|---|---|
| `{{module}}` | Module name (plural PascalCase) | `Tournaments` |
| `{module}` | Module name (singular PascalCase) | `Tournament` |
| `{moduleContainer}` | Module container directory | `Modules` |
| `{system}` | System container directory | `System` |
| `{{routemodule}}` | Lowercase plural for routes/components | `tournaments` |
| `{{routemodule_singular}}` | Lowercase singular for route parameters | `tournament` |

File naming strategy per stub (set in `$moduleStubFiles` / `$webModuleStubFiles`):

| Strategy | Result |
|---|---|
| `prefixed` | `{ModuleName}{StubFilename}.php` e.g. `TournamentsController.php` |
| `replaced` | `{ModuleName}.php` e.g. `Tournament.php` |
| `none` | Filename taken verbatim from stub config e.g. `ServiceProvider.php`, `api.php` |

Web stubs use an optional **4th config element** to override the output filename, allowing `WebController.stub` to produce `ClubsController.php` instead of `ClubsWebController.php`:

```php
'WebController.stub' => ['Controllers', true, 'prefixed', 'Controller.php'],
//                                                          ^^^^^^^^^^^^^^ output filename override
```

**API stubs** (used without `--web`):

| Stub | Generates |
|---|---|
| `Controller.stub` | `{Module}sController.php` — JSON API controller |
| `CreateRequest.stub` | `{Module}CreateRequest.php` — extends `BaseRequest` |
| `UpdateRequest.stub` | `{Module}UpdateRequest.php` — extends `BaseRequest` |
| `api.stub` | `api.php` — `Route::apiResource(...)` |
| `web.stub` | `web.php` — empty placeholder |

**Web stubs** (used with `--web`):

| Stub | Generates |
|---|---|
| `WebController.stub` | `{Module}sController.php` — Inertia controller extending `BaseWebController` |
| `WebCreateRequest.stub` | `{Module}CreateRequest.php` — extends `BaseWebRequest` |
| `WebUpdateRequest.stub` | `{Module}UpdateRequest.php` — extends module's `CreateRequest` |
| `WebRoute.stub` | `web.php` — auth+verified middleware, manage prefix, POST-for-update |

**Shared stubs** (used by both modes):

| Stub | Generates |
|---|---|
| `Filter.stub` | `{Module}sFilter.php` |
| `Interface.stub` | `{Module}Interface.php` |
| `Model.stub` | `{Module}.php` (module-level model) |
| `Repository.stub` | `{Module}Repository.php` |
| `ServiceProvider.stub` | `ServiceProvider.php` |
| `SystemModel.stub` | `{Module}.php` (system-level model) |

---

## Suggested Improvements

The following are areas where the base system could be enhanced. These are architectural observations, not bugs.

---

### 1. Use HTTP 422 for Validation Errors

**Current:** `BaseRequest` and `responseValidationError()` return **HTTP 417 (Expectation Failed)** for validation errors.

**Problem:** 417 is not a standard code for validation. The REST community and Laravel itself use **422 Unprocessable Entity** for this purpose. Many API clients and OpenAPI specs expect 422.

**Suggested fix:**

```php
// In BaseRequest.php and SystemResponseTrait.php
// Replace HTTP_EXPECTATION_FAILED (417) with HTTP_UNPROCESSABLE_ENTITY (422)
```

---

### 2. `getById` Ignores Its `$attributes` Parameter

**Current:**

```php
public function getById(string $id, array $attributes = []): BaseModel
{
    return $this->model->newQuery()->findOrFail($id); // $attributes never used
}
```

**Problem:** The interface contract includes `$attributes` (intended for column selection), but the implementation ignores it — callers get false confidence they can limit selected columns.

**Suggested fix:**

```php
public function getById(string $id, array $attributes = []): BaseModel
{
    $query = $this->model->newQuery();
    if ($attributes) {
        $query->select($attributes);
    }
    return $query->findOrFail($id);
}
```

---

### 3. Filter Skips Falsy Values (e.g. `"0"`)

**Current:**

```php
call_user_func_array([$this, $key], array_filter([$value], 'strlen'));
```

**Problem:** `array_filter([$value], 'strlen')` removes values where `strlen($value) == 0` — it's meant to skip empty strings. However, if a valid filter value is the string `"0"` (e.g. filtering `gender = 0`), `strlen("0")` is `1`, so it actually works. But the intent is unclear and this pattern is fragile. A numeric `0` would be cast to `""` in some contexts.

**Suggested approach:** Check `$value !== null && $value !== ''` explicitly, or check `isset($value) && $value !== ''`.

---

### 4. `EditorLogs` Creates Unnecessary Model Instances in Boot Events

**Current:**

```php
static::creating(function ($model) {
    $self = new static(); // Creates a new model instance just to call editorProvider()
    $editor = $self->getEditor();
    // ...
});
```

**Problem:** A fresh model instance is instantiated inside every event callback. The `$model` passed to the event already has access to `editorProvider()` if the trait is being used.

**Suggested fix:** Call `editorProvider()` on `$model` directly:

```php
static::creating(function ($model) {
    $editor = $model->editorProvider();
    // ...
});
```

---

### 5. No Database Migration Stub in Code Generator

**Current:** Running `base-system:generate Player` creates all PHP classes but **no migration file**.

**Problem:** Every new module needs a table. Developers must manually run `php artisan make:migration` after generation.

**Suggested improvement:** Add an optional `--migration` flag that generates a timestamped migration stub with `created_by`, `updated_by`, `deleted_by`, and `id` pre-filled.

```bash
php artisan base-system:generate Player --migration
```

---

### 6. No Built-in ULID Support

**Current:** The project uses ULIDs as primary keys (documented in CLAUDE.md), but `BaseModel` has no ULID configuration. Each model must manually declare `HasUlids` and `$incrementing = false`.

**Suggested fix:** Add ULID as the default key strategy in `BaseModel`:

```php
use Illuminate\Database\Eloquent\Concerns\HasUlids;

abstract class BaseModel extends Model
{
    use HasUlids;

    public $incrementing = false;
    protected $keyType = 'string';
}
```

Models that need integer keys can override.

---

### 7. No Transaction Support in Repository

**Current:** `create`, `update`, and `destroy` run without database transactions. If a lifecycle event handler fails mid-operation, the database can be left in a partially modified state.

**Suggested improvement:** Wrap mutating operations in `DB::transaction()`:

```php
public function create(array $attributes): BaseModel
{
    return DB::transaction(function () use ($attributes) {
        $model = $this->model->newInstance();
        $model->fill($attributes);
        $this->dispatchEvent('creating', $model);
        $model->save();
        $this->dispatchEvent('created', $model);
        return $model;
    });
}
```

---

### 8. `BaseInterface` Is Tightly Coupled to `BaseModel` Return Types

**Current:**

```php
public function create(array $attributes): BaseModel;
public function getById(string $id, array $attributes = []): BaseModel;
```

**Problem:** Returning `BaseModel` from the interface forces callers to cast to the actual subtype or use `@var` annotations. TypeScript-style generics don't exist in PHP, but you can use `@template` PHPDoc to make IDEs aware of the concrete type:

```php
/**
 * @template T of BaseModel
 */
interface BaseInterface
{
    /** @return T */
    public function create(array $attributes): BaseModel;
}
```

---

### 9. `Collection → Paginator` Conversion in `baseResponse` Has Wrong Semantics

**Current:**

```php
if ($body instanceof Collection) {
    $body = new Paginator($body->all(), $body->count());
}
```

**Problem:** `Paginator` constructor expects `($items, $perPage)`. Using `$body->count()` as `$perPage` is semantically wrong — it sets per-page equal to total count. The intent seems to be converting a collection to a "simple" paginated structure, but this creates a misleading paginator.

**Suggested fix:** Either pass the collection directly as JSON (it already serialises to an array), or use `LengthAwarePaginator` if pagination metadata is needed.

---

### 10. No API Versioning Support in SystemRouteProvider

**Current:** API routes are registered under `/api` with no versioning support.

**Suggested improvement:** Add a `api_version` config key and prefix routes accordingly:

```php
// config/base-system.php
'api_version' => 'v1',

// Routes registered as /api/v1/players
```

---

### 11. `translateCodeText` Has Redundant Lookup Chain

**Current:**

```php
private function translateCodeText(string $codeText): string
{
    if (Lang::has('base-system.' . $codeText)) { ... }
    if (Lang::has($codeText)) { ... }
    if (Lang::has('base-system::base-system.' . $codeText)) { ... }
    return $codeText;
}
```

**Problem:** Three lookup attempts for every error response. This is defensive but inefficient and the fallback order is non-obvious.

**Suggested fix:** Pick one canonical pattern (e.g., `base-system.{code}`) and document that as the override point. The package namespace lookup (`base-system::base-system.`) is for when the package's lang files aren't published — standardise one path.

---

### 12. Add a Service Layer Option

**Current:** Business logic sits directly in controllers or repositories. There is no dedicated service layer.

**Suggested addition:** Add a `Service.stub` and generate a service class between the controller and repository. This is especially useful when:

- Multiple repositories need to collaborate
- Complex business rules don't belong in the repository (which should only do data access)
- Transactions span multiple models

```
Controller → Service → Repository(ies)
```

---

### 13. `ModuleGenerateCommand` Has No `--dry-run` Option

**Current:** Running the generator immediately writes files with no preview.

**Suggested improvement:** Add a `--dry-run` flag that lists what files would be created without writing them. This helps developers verify the output before committing.

---

### 14. No Test Scaffolding in Generator

**Current:** No test files are generated for new modules.

**Suggested addition:** Generate a basic `Feature` test stub covering the five CRUD endpoints. This encourages test coverage from the start.

---

### 15. `sortableColumns` Whitelist Check Happens Before Key Mapping

**Current:**

```php
if (!in_array($orderColumn, $this->model->sortableColumns(), true)) {
    $orderColumn = $this->model->defaultSortColumn();
}
$orderColumn = $this->model->mapSortKey($orderColumn);
```

**Problem:** If you map a client-facing key (`full_name`) to a DB expression (`CONCAT(...)`), you must put the client-facing key (`full_name`) in `sortableColumns`. This works, but is non-obvious. If the order were reversed (map first, then check), you'd put the DB column in `sortableColumns`, which is closer to the DB concern.

This is a minor design trade-off. Document clearly which value (client key or DB column) belongs in `sortableColumns`.

---
