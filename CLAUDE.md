# DojoHub — Project Context

DojoHub is a martial arts tournament management system: tournaments, players, clubs, venues.

**App URL**: http://dojohub_react.test (served by Laravel Herd — always available, no `php artisan serve` needed).

> **Read the wiki before editing.** The detailed reference for every topic below lives under `wiki/`. CLAUDE.md is a map; the wiki is the territory.

---

## Wiki

All authoritative docs live under [`wiki/`](wiki/README.md).

| Document | What's in it |
|----------|--------------|
| [wiki/README.md](wiki/README.md) | Wiki index |
| [wiki/Architecture.md](wiki/Architecture.md) | System vs Module model split, Repository pattern, Interface bindings, BaseController/BaseRepository |
| [wiki/Conventions.md](wiki/Conventions.md) | Routes, PUT vs POST, soft-delete + FK RESTRICT, file naming, Pint workflow |
| [wiki/Frontend.md](wiki/Frontend.md) | Pages, Inertia, TypeScript, forms, dark mode, data tables |
| [wiki/Enums.md](wiki/Enums.md) | All backend enums (canonical integer values) |
| [wiki/Database.md](wiki/Database.md) | Authoritative schema — every table, FK, index, migration |
| [wiki/VenuesModule.md](wiki/VenuesModule.md) | Venues (required address) |
| [wiki/ClubsModule.md](wiki/ClubsModule.md) | Clubs (nullable address, logo upload) |
| [wiki/ClubPlayers.md](wiki/ClubPlayers.md) | Club membership history sub-feature |
| [wiki/PlayersModule.md](wiki/PlayersModule.md) | Players (standard repository, profile photo) |
| [wiki/TournamentsModule.md](wiki/TournamentsModule.md) | Tournament → Category → Bracket → Match → MatchCompetitor |
| [base_system/README.md](base_system/README.md) | Local `base_system` package (BaseModel/Repository/Controller, code generator) |

When a module wiki and `wiki/Database.md` conflict on schema, the database wiki wins. When `wiki/Architecture.md` and a module wiki disagree on a pattern, the **module wiki wins** — it tracks real code.

---

## Tech stack

**Backend**: Laravel 12.10 · PHP 8.4 · Eloquent (ULID PKs) · Inertia.js 2 (server-side rendering) · Ziggy 2.5
**Frontend**: React 19 · TypeScript 5.7 (strict) · Vite 6 · Tailwind 4 · shadcn/ui (Radix UI) · TanStack React Table 8 · react-brackets · date-fns
**Tooling**: ESLint 9, Prettier 3 (Tailwind sort), Laravel Pint, PHPUnit 11

---

## Architecture in one breath

Every domain entity has **two** Eloquent models:

| Layer | Path | Purpose |
|-------|------|---------|
| **System model** | `app/System/{Entity}/Models/{Entity}.php` | Canonical — fillable, casts, relationships |
| **Module model** | `app/Modules/{Plural}/Models/{Entity}.php` | Thin extension point — usually empty, e.g. `class Venue extends \App\System\Venue\Models\Venue {}` |

Module code always references the **module-level** class. Both extend `app/System/AppBaseModel.php`, which bundles `HasUlids`, `SoftDeletes`, `HasFactory`, and the `EditorLogs` audit trait.

**Repositories** extend `BaseSystem\Repositories\BaseRepository` and implement an interface that extends `BaseInterface`. Controllers depend on the **interface**, not the concrete class. Bindings live in each module's `ServiceProvider.php` (auto-discovered by `BaseSystemProvider`).

**Multi-table writes** (Venue + Address, Club + Address) belong in repository methods (`createVenue`, `updateVenue`, `deleteVenue`), not the controller. The repository injects every involved model via its constructor.

**Eager loading** uses the `onBeforeResult(Builder $builder)` hook on the repository — and you must override `getById()` to attach the same `with(...)` because `getById` doesn't go through the hook.

Full details: [wiki/Architecture.md](wiki/Architecture.md).

---

## Module skeleton

```
app/Modules/{Plural}/
├── Controllers/          # Thin — delegate to repository, return Inertia\Response or RedirectResponse
├── Exceptions/
├── Filters/              # extends BaseFilter — public methods become query-string filters
├── Interfaces/           # extends BaseInterface — declares repository contract
├── Models/               # Thin module-level models extending System models
├── Repositories/         # extends BaseRepository — implements interface
├── Requests/             # extends BaseWebRequest / BaseWebListRequest
├── Routes/
│   ├── api.php           # (optional) JSON API routes
│   └── web.php           # Inertia routes — auth+verified middleware, manage prefix
├── Services/             # (optional) multi-repository business logic
└── ServiceProvider.php   # Binds Interface → Repository (auto-registered)
```

Existing modules: **Clubs**, **Players**, **Tournaments**, **Users**, **Venues**.

---

## Routes

All web modules share:

- Middleware: `['auth', 'verified']`
- Prefix: `manage` (i.e. `/manage/venues`, `/manage/tournaments/...`)
- Resource update method:
  - **PUT** for modules without file uploads (Venues, Tournament Categories)
  - **POST** for modules with file uploads (Clubs, Players, Tournaments) — Inertia's `router.put()` transparently sends `_method`
- Route names: dot notation. Nested deeply for Tournaments:
  - `tournaments.categories.index`
  - `tournaments.categories.registrations.index`
  - `tournaments.categories.matches.update-result`

Always use `route('name', params)` (Ziggy) and Inertia `<Link>` for navigation.

---

## Database

Every domain table:
- ULID primary key (`$table->ulid('id')->primary()`)
- `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`
- Soft-delete via Eloquent `SoftDeletes`

**Soft-delete + FK RESTRICT** is safe by design: `SoftDeletes` issues an UPDATE (setting `deleted_at`), which does not trigger an `ON DELETE RESTRICT` FK. Repositories soft-delete the related row first, then the parent (see Venues/Clubs).

Tournament hierarchy:

```
Tournament
  └── TournamentCategory
        ├── TournamentRegistration   (player + club + age/weight snapshot)
        ├── TournamentBracket
        │     └── TournamentMatch
        │           └── TournamentMatchCompetitor   (registration_id + position: White=0, Blue=1)
        └── TournamentResult         (final positions)
```

Match competitors live in `TournamentMatchCompetitor` — there is no `player1_id`/`player2_id` on `tournament_matches`. `winner_id` on the match is a denormalised shortcut.

Full schema: [wiki/Database.md](wiki/Database.md). Full enum list: [wiki/Enums.md](wiki/Enums.md).

---

## Frontend in one breath

```
resources/js/
├── app.tsx, ssr.tsx
├── components/{ui, app-*, svg}
├── hooks/{use-appearance, use-initials, use-mobile}
├── layouts/{app-layout, auth-layout, app/}
├── pages/{auth, clubs, players, tournaments, venues, settings, dashboard, welcome}
├── types/
└── utils/
```

Pages are Inertia components — props come from the Laravel controller. Navigation via `<Link>`; forms via `useForm`. Path alias `@/` resolves to `resources/js/`. Dark mode is mandatory for every new page (`dark:` variants).

Tournament pages include `categories/{index,create,edit}.tsx`, `registrations/index.tsx`, `matches/index.tsx`.

Full guide: [wiki/Frontend.md](wiki/Frontend.md).

---

## Daily commands

```bash
composer dev          # Serve + queue + pail + vite (one terminal, all services)
composer dev:ssr      # Same with SSR
npm run dev           # Vite only
npm run build         # Production build
npm run types         # tsc strict check
npm run lint          # ESLint auto-fix
npm run format        # Prettier
composer test         # PHPUnit suite

vendor/bin/pint --dirty           # Format PHP — run after every PHP change
php artisan test --filter=Name    # Run one test
php artisan migrate               # Migrations
php artisan migrate:fresh --seed  # Reset DB
```

Scaffold a new module:

```bash
php artisan base-system:generate Tournament --web   # Inertia module (web)
php artisan base-system:generate Tournament         # JSON API module
```

The generator does not produce a migration — write that separately.

---

## Laravel Boost MCP

This project ships with the **Laravel Boost MCP server**. Prefer its tools when they fit:

- `search-docs` — version-pinned Laravel/Inertia/Tailwind docs. Use it **before** reaching for WebSearch when working on Laravel-ecosystem packages. Multiple short queries are better than one long one.
- `list-artisan-commands` — confirm flags and signatures before running `php artisan ...`.
- `get-absolute-url` — when sharing a URL with the user, use this to render the correct scheme/host/port for Herd.
- `tinker` / `database-query` — debug models or run a read-only query. Skip these when a feature test would prove the same thing — tests are preferred.
- `browser-logs` — read recent browser console output for frontend bugs.

See the `<laravel-boost-guidelines>` block below for the full rule set.

---

## What NOT to do

- Don't reference the System model directly from module code — always go through the module-level extension.
- Don't put address/multi-table logic in the controller — repository methods own that lifecycle.
- Don't use raw `DB::table(...)` — use Eloquent.
- Don't add the `update` route via `Route::resource()` when the module uses POST-for-update — use `->except('update')` then register POST explicitly.
- Don't eager-load only in `onBeforeResult` and forget to override `getById`.
- Don't hard-delete (`forceDelete`) without auditing FK references first.
- Don't bypass `<Link>` / `useForm` with raw `<a>` or `fetch()` — Inertia needs control of the navigation lifecycle.
- Don't create wiki/docs files unless explicitly asked. Update existing ones in `wiki/` instead.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.18
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- tightenco/ziggy (ZIGGY) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/react (INERTIA) - v2
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: https?://[kebab-case-project-dir].test. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(s). It is _always_ available through Laravel Herd.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- Build forms using the `useForm` helper. Use the code examples and `search-docs` tool with a query of `useForm helper` for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit <name>` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== inertia-react/core rules ===

## Inertia + React

- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="react">

import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>

</code-snippet>


=== inertia-react/v2/forms rules ===

## Inertia + React Forms

<code-snippet name="Inertia React useForm Example" lang="react">

import { useForm } from '@inertiajs/react'

const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit(e) {
    e.preventDefault()
    post('/login')
}

return (
<form onSubmit={submit}>
    <input type="text" value={data.email} onChange={e => setData('email', e.target.value)} />
    {errors.email && <div>{errors.email}</div>}
    <input type="password" value={data.password} onChange={e => setData('password', e.target.value)} />
    {errors.password && <div>{errors.password}</div>}
    <input type="checkbox" checked={data.remember} onChange={e => setData('remember', e.target.checked)} /> Remember Me
    <button type="submit" disabled={processing}>Login</button>
</form>
)

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>