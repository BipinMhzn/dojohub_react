# Frontend Architecture Guideline

How `resources/js/` is organized and where things go in dojohub_react.

---

## Stack

| Layer | Tool / Version |
|-------|----------------|
| Framework | React 19 |
| Server bridge | Inertia.js 2 (`@inertiajs/react`) |
| TypeScript | 5.7 (strict mode) |
| Build | Vite 6 |
| Styling | Tailwind CSS 4 (`@tailwindcss/vite`) |
| UI primitives | Radix UI (via shadcn/ui) |
| Tables | TanStack React Table 8 |
| Routing helper | Ziggy 2 (Laravel route names in JS) |
| Icons | lucide-react |
| Dates | date-fns |
| Toasts | sonner |

---

## Top-level layout

```
resources/js/
├── app.tsx                # Entry point — createInertiaApp
├── ssr.tsx                # SSR entry — for inertia:start-ssr
├── components/            # Reusable React components
│   ├── ui/                # shadcn/ui primitives (Button, Input, Dialog, ...)
│   ├── svg/               # Inline SVG components
│   ├── brackets/          # Tournament bracket renderers
│   └── *.tsx              # App-level components (app-header, breadcrumbs, ...)
├── hooks/                 # Custom React hooks (use-*.tsx)
├── layouts/               # Page layouts
│   ├── app-layout.tsx     # Top-level app shell wrapper
│   ├── auth-layout.tsx    # Auth pages wrapper
│   ├── app/               # Sub-layouts for /manage section
│   ├── auth/              # Auth sub-layouts (card, simple, split)
│   ├── settings/          # Settings sub-layout
│   ├── tournament/        # Per-tournament breadcrumb/header
│   ├── club/              # Per-club layout
│   ├── venue/             # Per-venue layout
│   └── category/          # Per-tournament-category layout
├── pages/                 # Inertia pages — mapped 1-to-1 with backend `Inertia::render(...)` calls
│   ├── auth/              # login, register, forgot-password, reset-password
│   ├── clubs/             # index, create, edit, show + partials
│   ├── coaches/
│   ├── players/
│   ├── referees/
│   ├── tournaments/       # nested: categories/, registrations/, brackets/, matches/
│   ├── venues/            # index, create, edit, show + mats/
│   ├── settings/          # profile, password, appearance
│   ├── dashboard.tsx
│   └── welcome.tsx
├── types/                 # TypeScript declarations
│   ├── index.d.ts         # SharedData, NavItem, BreadcrumbItem, User, …
│   ├── global.d.ts        # Window / process declarations
│   └── vite-env.d.ts      # Vite-injected env types
├── utils/                 # Pure utility functions
│   ├── countries.tsx
│   └── date.tsx
└── lib/
    └── utils.ts           # cn() Tailwind class merger + helpers
```

Path alias: **`@/`** resolves to `resources/js/` (configured in `vite.config.ts` and `tsconfig.json`).

---

## Where to put new code

| If you're adding… | Put it here |
|-------------------|-------------|
| A new page (corresponds to a controller `Inertia::render(...)`) | `pages/{module}/{action}.tsx` |
| A page-specific helper (only used by one page tree) | `pages/{module}/partials/{name}.tsx` |
| A reusable component used in 2+ places | `components/{kebab-name}.tsx` |
| A shadcn/ui primitive added via the CLI | `components/ui/{primitive}.tsx` |
| A custom hook (`useX`) | `hooks/use-{kebab}.tsx` |
| A layout (wraps page contents) | `layouts/{name}.tsx` |
| A pure utility function | `utils/{topic}.tsx` |
| A type definition consumed by multiple pages | `types/index.d.ts` |
| A type tied to one page or feature | Co-locate in the page file or its `partials/` |

---

## File naming

| File type | Convention | Example |
|-----------|------------|---------|
| Pages | kebab-case path mirroring URL | `pages/tournaments/categories/index.tsx` |
| Components | kebab-case file, PascalCase default export | `app-sidebar.tsx` → `AppSidebar` |
| Hooks | `use-{kebab}.tsx` file, `useFoo` export | `use-appearance.tsx` → `useAppearance` |
| Layouts | kebab-case | `app-layout.tsx`, `auth-layout.tsx` |
| Types | kebab-case, PascalCase types | `types/index.d.ts` → `BreadcrumbItem` |
| Partials | inside `pages/{module}/partials/{name}.tsx` | `pages/venues/partials/columns.tsx` |

`.tsx` for files containing JSX. `.ts` for pure logic (rare — `lib/utils.ts`).

---

## Inertia page resolution

`app.tsx` configures auto-resolve:

```tsx
resolve: (name) => resolvePageComponent(
    `./pages/${name}.tsx`,
    import.meta.glob('./pages/**/*.tsx')
),
```

A backend `Inertia::render('venues/index')` therefore loads `pages/venues/index.tsx`. The string must match the file path (case-sensitive on Linux).

**Conventions:**
- Page component names match the URL segments (lowercase, kebab-case folder names)
- Each page exports a `default` function component named `PascalCase` (e.g. `Venues`, `CreateVenue`)
- Use `<Head title="…" />` on every page for the browser tab title

---

## Layouts

Every page wraps itself in a layout — there's **no automatic layout wrapping**. The convention is:

```tsx
export default function Venues({ venues, filters }: VenuesProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Venues" />
            {/* page content */}
        </AppLayout>
    );
}
```

| Layout | Use for |
|--------|---------|
| `AppLayout` | All authenticated `/manage/*` pages |
| `AuthLayout` | Login, register, password reset |
| Per-feature layouts (`layouts/tournament/`, `layouts/venue/`) | Detail/edit pages that share a breadcrumb header |

Pass `breadcrumbs={BreadcrumbItem[]}` to `AppLayout` to populate the breadcrumb bar.

---

## Imports — use `@/`

Always import from `@/...`, never relative paths between top-level folders:

```tsx
// ✅
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

// ❌
import Heading from '../../components/heading';
```

Relative imports are fine **within** the same folder tree (e.g. a page importing its own `partials/columns.tsx`).

---

## Dark mode is mandatory

Every new page/component must include `dark:` variants for color classes. The `useAppearance` hook + `initializeTheme()` (in `app.tsx`) toggle `data-theme="dark"` on `<html>`.

```tsx
// ✅
<div className="bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100">

// ❌
<div className="bg-white text-gray-900">
```

Test in both themes via the appearance toggle in the user menu.

---

## Bundling & dev workflow

```bash
npm run dev          # Vite dev server with HMR
npm run build        # Production build
npm run build:ssr    # Production + SSR bundle (for inertia:start-ssr)
composer dev         # Serve + queue + pail + vite (all-in-one)
composer dev:ssr     # composer dev + SSR
```

If the user reports they don't see a change, ask them to run `npm run dev` or `npm run build` (and `composer run dev` if you stopped the all-in-one stack).

---

## Quality tooling

```bash
npm run lint         # ESLint --fix
npm run format       # Prettier --write resources/
npm run format:check # Prettier --check (CI)
npm run types        # tsc --noEmit (strict mode)
```

Prettier is configured with `prettier-plugin-tailwindcss` (auto-sorts Tailwind classes) and `prettier-plugin-organize-imports` (auto-sorts imports). Don't fight the tooling.

---

## Common pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| Page file path doesn't match the backend `Inertia::render(...)` string | Match casing exactly; Linux is case-sensitive |
| Component imported from a relative path | Use `@/components/...` |
| Forgetting a layout wrapper | Every page wraps itself; no auto-layouts |
| Missing dark mode variants | Audit every color class for `dark:` |
| Raw `<a href="…">` for internal links | Use `<Link href={...}>` from `@inertiajs/react` — preserves SPA navigation |
| Raw `fetch()` for mutations | Use `useForm()` or `router.post/put/delete` — keeps Inertia state in sync |
| Placing a page-specific helper in `components/` | Co-locate in `pages/{module}/partials/` |
| Importing a Tournament type from a Venues page | Lift to `types/index.d.ts` if shared |

---

## Related docs

- [react-patterns.md](react-patterns.md) — Component patterns, props, state
- [typescript-conventions.md](typescript-conventions.md) — Type system rules
- [forms-and-validation.md](forms-and-validation.md) — useForm patterns
- [wiki/Frontend.md](../../wiki/Frontend.md) — Full frontend reference
- `resources/js/app.tsx` — Entry point
- `resources/js/pages/venues/index.tsx` — Reference list page
- `resources/js/pages/venues/create.tsx` — Reference form page
