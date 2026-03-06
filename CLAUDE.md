# DojoHub - Project Context

## Wiki / Reference Docs

| Document | Path |
|----------|------|
| Database Schema (all tables, enums, migrations) | [database/DatabaseWiki.md](database/DatabaseWiki.md) |
| Base System architecture & code generator | [base_system/README.md](base_system/README.md) |
| Venues module | [app/Modules/Venues/VenueModuleWiki.md](app/Modules/Venues/VenueModuleWiki.md) |
| Clubs module | [app/Modules/Clubs/ClubModuleWiki.md](app/Modules/Clubs/ClubModuleWiki.md) |

---

## Project Overview
DojoHub is a full-stack martial arts management system for organizing tournaments, managing players, clubs, and venues. Built with modern web technologies for scalability and maintainability.

**Application URL**: http://dojohub_react.test

## Tech Stack

### Backend
- **Framework**: Laravel 12.10 (PHP 8.4+)
- **ORM**: Eloquent with ULID primary keys
- **Frontend Bridge**: Inertia.js 2.0 (server-side rendering)
- **Route Helper**: Ziggy 2.5 (exposes Laravel routes to frontend)

### Frontend
- **Library**: React 19.0.0
- **Language**: TypeScript 5.7.2 (strict mode)
- **Build Tool**: Vite 6.0 with Laravel Vite Plugin
- **Styling**: Tailwind CSS 4.0.0
- **UI Components**: shadcn/ui (based on Radix UI)
- **Icons**: Lucide React
- **Data Tables**: TanStack React Table 8.21.3
- **Tournament Brackets**: react-brackets 0.4.7
- **Date Handling**: date-fns 3.6.0

### Development Tools
- **Linting**: ESLint 9.19.0 with TypeScript support
- **Formatting**: Prettier 3.4.2 (with Tailwind class sorting)
- **Testing**: PHPUnit 11.5.18

## Architecture Patterns

### Modular Architecture
The project follows a modular design pattern where each feature is organized into separate modules:

```
app/Modules/
├── Clubs/
├── Players/
├── Tournaments/     # Main feature area
├── Users/
└── Venues/          # ✓ Updated — uses addresses table (address_id FK + mat_count)
```

Each module contains:
- **Controllers/**: HTTP request handlers (Inertia responses)
- **Database/Models/**: Eloquent ORM models
- **Repositories/**: Data access layer (repository pattern)
- **Requests/**: Form validation classes
- **Routes/**: Module-specific routes (web.php, api.php)
- **Filters/**: Query filtering logic
- **Interfaces/**: Module contracts
- **Middlewares/**: Module-specific middleware

### Base System Layer
`app/System/` contains base models extended by modules:
- AppBaseModel (base for all models — HasUlids, SoftDeletes, EditorLogs, HasFactory)
- Address (shared address entity used by Venue and Club)
- Venue (address_id FK + mat_count)
- Club, Player, User
- Tournament, TournamentCategory, TournamentRegistration
- TournamentBracket, TournamentMatch, TournamentMatchCompetitor, TournamentResult

### Shared Foundation
`base_system/src/` provides shared architecture:
- BaseController, BaseRepository
- Query Filters framework
- Base Request classes
- Artisan command stubs for scaffolding

### Backend Patterns
- **Repository Pattern**: Data access abstraction
- **Request Validation**: Form request classes for input validation
- **Trait Composition**: EditorTrait for audit logging (created_by, updated_by, created_at, updated_at)
- **Middleware**: HandleInertiaRequests, HandleAppearance

### Frontend Patterns
- **Component-Based**: Reusable React components with TypeScript
- **Layout System**: Separate layouts (app-layout, auth-layout)
- **Partial Components**: Reusable page sections in `partials/` directories
- **Path Aliases**: `@/` maps to `resources/js/`

## Database Schema

### Key Tables
- **users**: Authentication and user profiles
- **addresses**: Shared address records (used by venues and clubs). Contains address, city, state, country.
- **players**: Martial arts practitioners (DOB, weight, gender, phone, profile_photo)
- **clubs**: Organizations grouping players (clubs.address_id → addresses.id, nullable)
- **venues**: Event locations for tournaments (venues.address_id → addresses.id, required; plus mat_count)
- **clubs_players**: Club membership history pivot (started_at, ended_at)
- **tournaments**: Tournament event container (name, level, status, venue_id, start_at/end_at)
- **tournament_categories**: Competitive divisions within a tournament (gender + age + weight class)
- **tournament_registrations**: Player enrollments per category (snapshot age/weight at registration time)
- **tournament_brackets**: Competition structure per category (type, seeding_method, status)
- **tournament_matches**: Individual fights within a bracket (round, section, scheduling, winner progression)
- **tournament_match_competitors**: Per-match athlete record (position Blue/White, score, penalties, result)
- **tournament_results**: Final finishing positions per category

### Address Lifecycle
Addresses are managed at the repository level (not via DB cascades). The controller is thin — it calls custom repository methods (e.g. `createVenue`, `updateVenue`, `deleteVenue`). The repository injects both the entity model and the Address model and owns the full lifecycle.

Delete pattern is soft-delete safe: `SoftDeletes` issues an UPDATE (sets `deleted_at`), not a hard DELETE, so the RESTRICT FK constraint is not triggered. Sequence: soft-delete address → soft-delete entity.

- **Venues**: address is required — `createVenue` creates Address then Venue; `deleteVenue` soft-deletes address then venue.
- **Clubs**: address is nullable — same pattern applies when address_id is set.

### Key Conventions
- **Primary Keys**: ULID (not auto-increment integers)
- **Audit Trails**: All models have created_by, updated_by, created_at, updated_at
- **Foreign Keys**: Proper cascade/restrict actions
- **Timestamps**: Laravel timestamps on all tables

## Project Structure

### Frontend Organization
```
resources/js/
├── app.tsx                    # Inertia app entry point
├── ssr.tsx                    # Server-side rendering entry
├── components/
│   ├── ui/                    # 20+ shadcn/ui components (Button, Input, Dialog, etc.)
│   ├── app-*.tsx              # App-specific components (AppSidebar, AppBreadcrumbs, etc.)
│   └── svg/                   # Custom SVG components
├── hooks/
│   ├── use-appearance.tsx     # Dark mode management
│   ├── use-initials.tsx
│   └── use-mobile.tsx
├── layouts/
│   ├── app-layout.tsx         # Main app layout with sidebar
│   ├── auth-layout.tsx        # Authentication pages layout
│   └── app/                   # Layout components (sidebar, nav)
├── lib/                       # Utility functions
├── pages/                     # 32 page components (see below)
├── types/                     # TypeScript type definitions
└── utils/                     # Utility functions
```

### Page Components (32 pages)
```
pages/
├── auth/                      # Login, Register, Password reset/confirm, Email verification
├── clubs/                     # Club CRUD (index, create, edit)
├── players/                   # Player CRUD (index, create, edit)
├── tournaments/               # Tournament management
│   ├── index.tsx              # List tournaments
│   ├── create.tsx             # Create new tournament
│   ├── edit.tsx               # Edit tournament
│   ├── matches/               # Tournament brackets/matches (NEW)
│   │   └── index.tsx
│   ├── registrations/         # Player registration management
│   │   └── index.tsx
│   └── partials/              # Reusable tournament components
│       ├── create-form.tsx
│       ├── tournament-detail-header.tsx
│       ├── tournament-detail-info.tsx
│       ├── tournament-detail-players.tsx
│       └── tournament-registrations-columns.tsx
├── venues/                    # Venue CRUD (index, create, edit)
├── settings/                  # User settings
│   ├── profile.tsx            # User profile management
│   ├── appearance.tsx         # Dark mode toggle
│   └── password.tsx           # Password change
├── dashboard.tsx              # Main dashboard
└── welcome.tsx                # Public welcome page
```

### UI Component System
All UI components follow shadcn/ui conventions:
- Located in `resources/js/components/ui/`
- Built with Radix UI primitives + Tailwind CSS
- Fully accessible and customizable
- TypeScript with proper prop types

Key components:
- Forms: Button, Input, Checkbox, Radio, Select, Textarea, Toggle
- Layout: Card, Sheet, Dialog, Popover, Tooltip, Sidebar
- Data: DataTable (TanStack Table), Avatar, Badge, Breadcrumb
- Utilities: Alert, Calendar, FileUploader, NavigationMenu

## Core Features

### 1. User Management
- Authentication (login, register, email verification, password reset)
- User profiles and settings
- Dark mode appearance toggle

### 2. Club Management
- CRUD operations for clubs
- Sortable/filterable data tables
- Club-player associations

### 3. Player Management
- CRUD operations for players
- Attributes: Name, Gender, DOB, Weight, Profile photo, Phone number
- Age calculation from DOB
- Club association

### 4. Venue Management
- CRUD operations for venues
- Tournament venue assignment

### 5. Tournament Management (Primary Feature)
- **Tournament CRUD**: Create, edit, list tournaments
- **Tournament Attributes**:
  - Basic: Name, Description, Logo
  - Categories: Weight (min/max), Age (min/max), Gender filter
  - Status: Scheduled, In Progress, Completed
  - Venue, Dates (start_at, end_at), Tournament level
- **Tournament Registrations**:
  - Register players based on tournament criteria
  - Calculate player age/weight at registration time
  - Club-Player-Tournament relationship tracking
  - Registration deletion/unregistration
  - Filtered player list based on tournament filters
- **Tournament Matches** (Current Development):
  - Bracket visualization with react-brackets
  - Multi-round tournaments (Quarterfinals, Semifinals, Finals)
  - Match scheduling and management

## Development Workflows

### Installation
```bash
composer install              # Backend dependencies
npm install                   # Frontend dependencies
php artisan migrate          # Run migrations
```

### Development
```bash
composer dev                 # Start all services (Laravel, Queue, Logs, Vite)
composer dev:ssr            # Start with server-side rendering
npm run dev                 # Start Vite dev server only
php artisan serve           # Start Laravel server only
```

### Build
```bash
npm run build               # Production build
npm run build:ssr          # Production build with SSR
```

### Code Quality
```bash
npm run lint               # ESLint (auto-fix)
npm run format             # Prettier format
npm run format:check       # Check formatting only
npm run types              # TypeScript type checking
composer test              # PHPUnit tests
```

### Database
```bash
php artisan migrate        # Run migrations
php artisan migrate:fresh  # Fresh migration
php artisan db:seed        # Seed database
```

## TypeScript Configuration

### Path Aliases
```typescript
import { Button } from '@/components/ui/button'
import { useAppearance } from '@/hooks/use-appearance'
```

`@/` resolves to `resources/js/`

### Important Types
- `PageProps`: Global page props type (user, auth, etc.)
- `User`: User model type
- `Player`: Player model type
- `Tournament`: Tournament model type
- `TournamentRegistration`: Tournament registration type
- Various Inertia types for type-safe routing

## Key Conventions

### Backend
- Use repository pattern for data access
- Form validation in Request classes (not controllers)
- Return Inertia responses: `Inertia::render('PageName', $data)`
- Use EditorTrait on models for audit logging
- ULIDs for primary keys (not integers)

### Frontend
- Use TypeScript strictly (no `any` types)
- Import components from `@/components/ui/`
- Use Inertia Link for navigation: `<Link href={route('...')}>`
- Access routes with Ziggy: `route('tournaments.index')`
- Follow shadcn/ui component patterns
- Use `cn()` utility for conditional Tailwind classes
- Dark mode via `use-appearance` hook

### Code Style
- Frontend: Prettier with Tailwind class sorting
- Backend: Laravel Pint (PSR-12 style)
- ESLint for React/TypeScript best practices
- Strict TypeScript mode enabled

### File Naming
- Frontend: kebab-case for files, PascalCase for components
- Backend: StudlyCase for classes, snake_case for files
- Routes: dot notation (e.g., `tournaments.registrations.index`)

## Current Development Status

**Active Branch**: `feature/matches`

**Recent Work**:
- Tournament registration system refactored
- Tournament detail page component breakdown
- Player registration fixes
- Tournament matches/bracket system (NEW - MatchesController added)

**Staged Changes**:
- MatchesController.php (new)
- matches/index.tsx page (new)
- Updated tournament routes
- Updated tournament registrations UI

## Important Notes

### Inertia.js Integration
- All pages are Inertia components (not traditional React SPA)
- Props passed from Laravel controllers appear as component props
- Use Inertia's `<Link>` for navigation (not React Router)
- Form submissions use Inertia's form helper: `useForm()`

### Dark Mode
- Managed via `use-appearance` hook
- Persisted in user settings
- Uses Tailwind's dark mode classes
- Toggle available in settings/appearance page

### Data Tables
- Use TanStack React Table for complex tables
- Column definitions in separate files (e.g., `*-columns.tsx`)
- Support sorting, filtering, pagination
- Reusable DataTable component

### Tournament Business Logic
- Age calculated at registration time (not tournament time)
- Weight checked at registration time
- Gender filter applied to player list
- Multiple clubs can register players
- Players can be registered/unregistered

### File Uploads
- Player profile photos
- Tournament logos
- Handled via FileUploader component

## Common Tasks

### Adding a New Module
1. Create module directory in `app/Modules/`
2. Create Controller, Model, Repository, Request classes
3. Define routes in `Routes/web.php`
4. Create frontend pages in `resources/js/pages/module-name/`

### Adding a New Page
1. Create page component in `resources/js/pages/`
2. Add route in module's `Routes/web.php`
3. Create controller method returning Inertia response
4. Add type definitions if needed

### Adding a UI Component
1. Use shadcn/ui CLI: `npx shadcn-ui@latest add [component]`
2. Component appears in `resources/js/components/ui/`
3. Customize as needed
4. Import and use: `import { Button } from '@/components/ui/button'`

### Adding a Database Table
1. Create migration: `php artisan make:migration create_table_name`
2. Define schema in migration file
3. Run migration: `php artisan migrate`
4. Create Eloquent model in appropriate module

## Troubleshooting

### Common Issues
- **Vite not building**: Check `vite.config.ts` and ensure Vite dev server is running
- **TypeScript errors**: Run `npm run types` to check, ensure imports use `@/` alias
- **Route not found**: Check Ziggy routes with `route()` helper, verify route defined in web.php
- **Inertia version mismatch**: Ensure backend and frontend Inertia versions match
- **Dark mode not working**: Check `use-appearance` hook and middleware

### Debug Tools
- Laravel Debugbar (if installed)
- React DevTools
- Laravel Pail for logs: `php artisan pail`
- Browser console for frontend errors
- `dd()` and `dump()` in Laravel for backend debugging

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