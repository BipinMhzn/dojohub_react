# DojoHub

A full-stack martial arts management system for organising judo tournaments, players, clubs, and venues.

Built on Laravel 12 + React 19 + Inertia.js 2 with a custom modular architecture (`base_system`) that scaffolds and wires modules consistently.

---

## Stack

| Layer | Tools |
|-------|-------|
| Backend | Laravel 12.10, PHP 8.4, Eloquent (ULID PKs), Inertia.js 2 (SSR), Ziggy 2.5 |
| Frontend | React 19, TypeScript 5.7 (strict), Vite 6, Tailwind 4, shadcn/ui (Radix UI), TanStack React Table 8, react-brackets, date-fns |
| Tooling | Laravel Pint, PHPUnit 11, ESLint 9, Prettier 3 (Tailwind sort) |

---

## Features

- **Venues** — physical locations with shared `addresses` table, mat-count tracking for scheduling.
- **Clubs** — martial arts clubs with logos, contact info, and a membership history pivot.
- **Players** — athletes with DOB, weight, gender, nationality, profile photos.
- **Club Players** — full membership history per player (start/end dates, belt rank, grade).
- **Tournaments** — event container with full lifecycle:
  - **Categories** — competitive divisions (gender + age + weight class)
  - **Registrations** — player enrollment with age/weight snapshot at registration time
  - **Brackets** — single-elimination, repechage, pool+final formats
  - **Matches** — bracket progression, scoring (waza-ari / shido), winner advancement
  - **Results** — final medal positions
- **Auth** — login, register, email verification, password reset, profile settings, dark mode.

---

## Getting started

### Prerequisites

- PHP 8.4+
- Composer
- Node 18+
- MySQL or compatible database
- [Laravel Herd](https://herd.laravel.com/) (the project is served at `http://dojohub_react.test`)

### Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

### Run

```bash
composer dev          # Serve + queue + pail logs + Vite (one terminal)
composer dev:ssr      # Same with server-side rendering
```

Then open <http://dojohub_react.test>.

If you don't use Herd:

```bash
php artisan serve     # Backend
npm run dev           # Vite dev server
```

---

## Common commands

```bash
# Build & quality
npm run build                     # Production frontend build
npm run build:ssr                 # Production build with SSR
npm run types                     # tsc strict check
npm run lint                      # ESLint auto-fix
npm run format                    # Prettier format
vendor/bin/pint --dirty           # Format PHP changes

# Testing
composer test                     # Full PHPUnit suite
php artisan test --filter=Name    # Single test by filter

# Database
php artisan migrate               # Run migrations
php artisan migrate:fresh --seed  # Drop everything and reseed
php artisan db:seed               # Seed only

# Scaffolding
php artisan base-system:generate Tournament --web   # Inertia module
php artisan base-system:generate Tournament         # JSON API module
```

---

## Project layout

```
app/
├── Modules/                # Feature modules (Clubs, Players, Tournaments, Users, Venues)
│   └── {Plural}/
│       ├── Controllers/    # Thin — delegate to repository
│       ├── Filters/        # extends BaseFilter
│       ├── Interfaces/     # Repository contracts
│       ├── Models/         # Thin extensions of System models
│       ├── Repositories/   # extends BaseRepository
│       ├── Requests/       # Form Request validation
│       ├── Routes/         # web.php, api.php
│       └── ServiceProvider.php
└── System/                 # Canonical models, enums, traits
    ├── AppBaseModel.php
    └── {Entity}/Models/    # Fillable, casts, relationships
        Foundation/Enums/   # Int-backed enums

base_system/                # Local package: BaseModel/Repository/Controller + code generator
database/                   # Migrations, factories, seeders
resources/js/               # React/Inertia frontend
tests/                      # PHPUnit feature + unit tests
wiki/                       # Project documentation (start here)
```

---

## Architecture in one breath

Every domain entity has **two** Eloquent models:

| Layer | Path | Purpose |
|-------|------|---------|
| System model | `app/System/{Entity}/Models/{Entity}.php` | Canonical — fillable, casts, relationships |
| Module model | `app/Modules/{Plural}/Models/{Entity}.php` | Thin extension point — usually empty |

Repositories extend `BaseSystem\Repositories\BaseRepository` and are bound to module interfaces in each module's `ServiceProvider.php`. Multi-table writes (e.g. Venue + Address) live in custom repository methods, not the controller. Soft-delete + `ON DELETE RESTRICT` FKs work together because `SoftDeletes` issues an UPDATE, not a DELETE.

See [`wiki/Architecture.md`](wiki/Architecture.md) for the full pattern.

---

## Documentation

| Document | What's in it |
|----------|--------------|
| [wiki/README.md](wiki/README.md) | Wiki index |
| [wiki/Architecture.md](wiki/Architecture.md) | System vs Module models, Repository pattern, Interface bindings |
| [wiki/Conventions.md](wiki/Conventions.md) | Routes, PUT vs POST, soft-delete + FK RESTRICT, file naming, Pint workflow |
| [wiki/Frontend.md](wiki/Frontend.md) | Pages, Inertia, TypeScript, forms, dark mode, data tables |
| [wiki/Enums.md](wiki/Enums.md) | All backend enums (canonical integer values) |
| [wiki/Database.md](wiki/Database.md) | Authoritative schema — every table, FK, index, migration |
| [wiki/VenuesModule.md](wiki/VenuesModule.md) | Venues (required address) |
| [wiki/ClubsModule.md](wiki/ClubsModule.md) | Clubs (nullable address, logo upload) |
| [wiki/ClubPlayers.md](wiki/ClubPlayers.md) | Club membership history sub-feature |
| [wiki/PlayersModule.md](wiki/PlayersModule.md) | Players (standard repository, profile photo) |
| [wiki/TournamentsModule.md](wiki/TournamentsModule.md) | Tournament → Category → Bracket → Match → MatchCompetitor |
| [base_system/README.md](base_system/README.md) | Local `base_system` package and code generator |
| [CLAUDE.md](CLAUDE.md) | AI assistant context (mirrors the wiki) |

---

## License

MIT
