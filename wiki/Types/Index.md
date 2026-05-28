# TypeScript Type System

All shared type definitions live under `resources/js/types/`. There are three files. Module-specific types (Player, Tournament, Venue, Club, Coach, Referee, etc.) are defined co-located with their page files rather than centralised here.

---

## `types/index.d.ts` — Shared Application Types

**File:** `resources/js/types/index.d.ts`

This file exports all interfaces that are used across multiple modules or that represent Inertia shared props.

### `Auth`

```ts
export interface Auth {
    user: User;
}
```

Holds the authenticated user. Available on every page via `usePage<SharedData>().props.auth.user`.

---

### `User`

```ts
export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}
```

Represents the authenticated user record. `avatar` is optional — when absent, the UI falls back to initials via `useInitials`. The `[key: string]: unknown` index signature allows the backend to attach additional fields without TypeScript errors.

---

### `SharedData`

```ts
export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    flash: FlashMessages;
    [key: string]: unknown;
}
```

The Inertia shared props object. Laravel's `HandleInertiaRequests` middleware merges these into every response. Every page component receives these automatically.

| Field | Source | Purpose |
|---|---|---|
| `name` | `config('app.name')` | App name shown in `AuthSplitLayout` header |
| `quote` | Server-defined | Inspirational quote shown on the auth split panel |
| `auth` | Current session | Authenticated user |
| `ziggy` | `Ziggy::generate()` | Route list + current URL for the `route()` helper |
| `sidebarOpen` | Session/cookie | Whether the sidebar starts open or collapsed |
| `flash` | `session()->flash()` | Toast notification messages |

**How to access shared data in a page component:**

```ts
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

const { auth, flash } = usePage<SharedData>().props;
```

The `[key: string]: unknown` index signature allows pages to receive additional props beyond the shared set — Inertia merges page-specific props from the controller on top of the shared props.

---

### `FlashMessages`

```ts
export interface FlashMessages {
    success: string | null;
    error:   string | null;
    info:    string | null;
    warning: string | null;
}
```

Four nullable message slots, one per toast severity. Set by the Laravel backend via `->with('success', 'Saved.')` on redirects. Consumed by `useFlashToast` to display sonner toasts. See [Hooks — useFlashToast](../Hooks/Index.md).

---

### `BreadcrumbItem`

```ts
export interface BreadcrumbItem {
    title: string;
    href: string;
}
```

A single breadcrumb link. Passed as an array to `AppLayout` and forwarded to `AppSidebarHeader` → `Breadcrumbs`.

---

### `NavGroup`

```ts
export interface NavGroup {
    title: string;
    items: NavItem[];
}
```

A labelled group of navigation items. Used in `app-sidebar.tsx` to organise the sidebar into sections.

---

### `NavItem`

```ts
export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}
```

A single sidebar navigation entry. `icon` is a Lucide icon component (optional). `isActive` is set dynamically by comparing the current URL.

---

### `PaginationLink`

```ts
export interface PaginationLink {
    label: string;
    url: string | null;
    active: boolean;
}
```

Matches the shape of Laravel's paginator link objects in the `links` array of a paginated response. Used by `components/ui/data-table-pagination.tsx` to render page number buttons. `url` is `null` for the disabled previous/next buttons when at the first/last page.

**Example Laravel paginator output:**

```json
{
  "links": [
    { "label": "&laquo; Previous", "url": null,                          "active": false },
    { "label": "1",               "url": "http://app.test/players?page=1", "active": true  },
    { "label": "2",               "url": "http://app.test/players?page=2", "active": false },
    { "label": "Next &raquo;",    "url": "http://app.test/players?page=2", "active": false }
  ]
}
```

---

### `PageFilters`

```ts
export interface PageFilters {
    keyword?:    string;
    sort_by?:    string;
    sort_order?: string;
    page?:       number;
    per_page?:   number;
    status?:     string;
}
```

The standard query parameter shape for all list (index) pages. Pages that display a data table pass these as Inertia `get` params via `router.get(url, filters)`. Every field is optional so partial updates work without resetting others.

| Field | Purpose |
|---|---|
| `keyword` | Full-text search string |
| `sort_by` | Column name to sort by |
| `sort_order` | `'asc'` or `'desc'` |
| `page` | Current page number (1-based) |
| `per_page` | Rows per page |
| `status` | Filter by status value |

---

### `Country` and `State`

```ts
export interface Country {
    name: string;
    states: State[];
}

export interface State {
    name: string;
    cities: string[];
}
```

Hierarchy type for the static geographic data in `utils/countries.tsx`. Note the naming: in the data file, `State` maps to Nepal's **provinces** and `cities` maps to **districts**. The naming follows a generic international convention rather than Nepal-specific terminology.

---

## `types/global.d.ts` — Ziggy Global Route Helper

**File:** `resources/js/types/global.d.ts`

```ts
import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;
}
```

Declares `route` as a global constant available everywhere in the TypeScript codebase without an import. The actual runtime value is:

- **In CSR (browser):** provided by Ziggy's Laravel blade directive or the `@routes` helper, which injects a script that sets `window.route`.
- **In SSR (Node.js):** set on `global.route` in `ssr.tsx`'s `setup` callback.

Because `route` is declared with `const` in the global scope, TypeScript will type-check all calls against Ziggy's `routeFn` type, including the `RouteName` generic that constrains the first argument to known route names.

**Usage in a component:**

```ts
<Link href={route('tournaments.index')}>Tournaments</Link>
```

TypeScript will error if `'tournaments.index'` is not a known route name (when Ziggy types are generated).

---

## `types/vite-env.d.ts` — Vite Environment Types

**File:** `resources/js/types/vite-env.d.ts`

```ts
/// <reference types="vite/client" />
```

The standard Vite triple-slash reference. Provides TypeScript types for:

- `import.meta.env` — typed as `ImportMetaEnv`, including built-in vars like `VITE_APP_NAME`, `MODE`, `DEV`, `PROD`, `SSR`.
- `import.meta.hot` — HMR API.
- Asset module imports (e.g. `import logo from './logo.png'` resolves to `string`).

---

## Module-Specific Types

Types for domain entities are defined **co-located** with the page files that use them, not in `types/index.d.ts`. This keeps the type definition close to the data shape it represents and avoids a monolithic type file.

Examples:

| Entity | Defined in |
|---|---|
| `Tournament`, `TournamentCategory` | `resources/js/pages/tournaments/index.tsx` (exported) |
| `Venue` | `resources/js/pages/venues/index.tsx` (exported) |
| `Club` | `resources/js/pages/clubs/index.tsx` (exported) |
| `Player` | `resources/js/pages/players/index.tsx` (exported) |
| `Coach` | `resources/js/pages/coaches/index.tsx` (exported) |

Domain layouts import these types directly from their page files:

```ts
// layouts/tournament/layout.tsx
import { Tournament } from '@/pages/tournaments';

// layouts/venue/layout.tsx
import { Venue } from '@/pages/venues/index';
```

---

## Related

- [Frontend Overview](../Frontend/Index.md)
- [Hooks — SharedData usage in useFlashToast](../Hooks/Index.md)
- [Utils — Country / State types](../Utils/Index.md)
- [Layouts — BreadcrumbItem usage](../Layouts/Index.md)
