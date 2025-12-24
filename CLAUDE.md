# DojoHub - Project Context

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
└── Venues/
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
- AppBaseModel (base for all models)
- Tournament, TournamentRegistration
- Player, Club, Venue, User

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
- **players**: Martial arts practitioners (DOB, weight, gender, phone, profile_photo)
- **clubs**: Organizations grouping players
- **venues**: Event locations for tournaments
- **tournaments**: Tournament definitions with filters (weight/age categories, gender, status)
- **tournament_registrations**: Player enrollments with calculated age/weight at registration time
- **Pivot tables**: Relationship management between entities

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
