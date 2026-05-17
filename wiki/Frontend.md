# Frontend

React 19 + TypeScript + Inertia 2 + Tailwind 4. Build tool: Vite 6.

---

## Directory layout

```
resources/js/
├── app.tsx                 # Inertia client entry
├── ssr.tsx                 # Server-side rendering entry
├── components/
│   ├── ui/                 # shadcn/ui primitives (button, dialog, calendar, …)
│   ├── app-*.tsx           # App chrome (AppSidebar, AppBreadcrumbs, …)
│   └── svg/                # Inline SVG components
├── hooks/
│   ├── use-appearance.tsx  # Dark mode toggle + persistence
│   ├── use-initials.tsx
│   └── use-mobile.tsx
├── layouts/
│   ├── app-layout.tsx      # Authenticated layout with sidebar
│   ├── auth-layout.tsx     # Login / register / password-reset layout
│   └── app/                # Layout subcomponents (sidebar nav, header)
├── lib/
├── pages/                  # Inertia page components — one per route
├── types/
│   ├── index.d.ts
│   ├── global.d.ts
│   └── vite-env.d.ts
└── utils/
```

`@/` is a path alias for `resources/js/`.

---

## Page components

Pages live under `resources/js/pages/{module}/`. Filenames are kebab-case, default exports are React components.

```
pages/
├── auth/                   login, register, forgot-password, reset-password, verify-email, confirm-password
├── clubs/                  index, create, edit, show
├── players/                index, create, edit
├── tournaments/
│   ├── index.tsx
│   ├── create.tsx
│   ├── edit.tsx
│   ├── categories/         index, create, edit
│   ├── registrations/      index
│   ├── matches/            index
│   └── partials/           Reusable subcomponents for tournament pages
├── venues/                 index, create, edit
├── settings/               profile, password, appearance
├── dashboard.tsx
└── welcome.tsx             Public landing
```

> Inertia's documentation suggests `Pages/` (PascalCase) — DojoHub uses lowercase `pages/` set in `vite.config.ts`. Match the existing convention; never rename.

---

## Inertia conventions

| Goal | API |
|------|-----|
| Render a page from the server | `return $this->render('module/page', $props)` (from `BaseWebController`) |
| Navigate without page reload | `<Link href={route('venues.edit', venue.id)}>` |
| Submit a mutation form | `useForm({...}).post(route('venues.store'))` |
| Resolve a route name to URL | Ziggy's `route('name', params)` |

Never use `<a href>` for internal links — use Inertia `<Link>` so the SPA navigation works.

Inertia v2 features available (use them when they fit):

- Polling
- Prefetching
- Deferred props — show a skeleton state while loading
- Infinite scroll with `WhenVisible`

When using deferred props, always pair with a pulsing / animated skeleton.

---

## Forms

Forms use Inertia's `useForm` helper. The pattern:

```tsx
import { useForm } from '@inertiajs/react';

const { data, setData, post, processing, errors } = useForm({
    name: '',
    mat_count: 1,
    address: '',
    city: '',
    state: '',
    country: '',
});

function submit(e: FormEvent) {
    e.preventDefault();
    post(route('venues.store'));
}
```

Form data is **flat**, even when the backend writes to multiple tables. The repository splits flat input into Address + Venue rows — see [VenuesModule.md](VenuesModule.md).

For modules with file uploads (Clubs, Players, Tournaments), `useForm` sends multipart automatically when any value is a `File`.

---

## Inertia request method (PUT vs POST)

Some routes are POST-for-update on the backend (file uploads). On the frontend, **always** call `router.put()` / `useForm().put(...)` — Inertia adds a `_method` field that the matching POST route handles.

```tsx
useForm({...}).put(route('clubs.update', club.id));  // even though route is POST
```

For modules without uploads (Venues, Tournament Categories), the route is genuinely PUT.

---

## TypeScript types

Shared types live in `resources/js/types/index.d.ts`. Per-module types are exported from their `pages/{module}/index.tsx` page component to keep them colocated.

### Page props shape

```typescript
type ClubsIndexProps = {
    clubs: {
        data: Club[];
        links: PaginationLink[];
    };
    filters: PageFilters;
};
```

### Nested address pattern

When a model has an eager-loaded address, the type uses a **nested** `address` field (not flat columns):

```typescript
export type Venue = {
    id: string;
    name: string;
    mat_count: number;
    address: VenueAddress;   // ← nested
    created_at: string;
    updated_at: string;
};
```

The form data is flat (`{ address, city, state, country }`), but the rendered model is nested.

### Enums

Enums are mirrored as TypeScript `enum` or const-object types matching the backend integer values:

```typescript
export enum PlayerGender {
    MALE = 0,
    FEMALE = 1,
}

export const BELT_RANK_LABELS: Record<BeltRank, string> = {
    0: 'White',
    1: 'Yellow',
    ...
};
```

See each module wiki for its enum exports.

---

## UI components

All UI primitives live in `resources/js/components/ui/` and follow shadcn/ui conventions (Radix UI under the hood). Add new ones via:

```bash
npx shadcn-ui@latest add {component}
```

Current set: Button, Input, Checkbox, Select, Textarea, Toggle, Card, Sheet, Dialog, Popover, Tooltip, Sidebar, Avatar, Badge, Breadcrumb, Alert, Calendar, FileUploader, NavigationMenu, plus a `DataTable` wrapper around TanStack React Table.

### Styling

- Use Tailwind utility classes. Class sorter is on in Prettier — let it sort.
- For repeated patterns, extract into a component instead of duplicating long class strings.
- Use `gap-*` utilities for list spacing, not `mr-*` / `ml-*` margins.
- Conditional classes: `cn()` helper from `lib/`.

### Dark mode

- `use-appearance` hook toggles dark mode and persists the choice.
- New pages must support dark mode using `dark:` variants (e.g. `bg-white dark:bg-zinc-900`).

### Tailwind v4 specifics

- Tailwind is imported via `@import "tailwindcss";` (not the v3 `@tailwind` directives).
- Deprecated v3 utilities are removed — use the v4 replacements (`bg-black/50` instead of `bg-opacity-*`, `shrink-*` instead of `flex-shrink-*`, etc.).

---

## Data tables

TanStack React Table 8. Pattern:

| File | Role |
|------|------|
| `pages/{module}/index.tsx` | Page component, passes data to `DataTable` |
| `pages/{module}/partials/columns.tsx` | `ColumnDef[]` array — one per visible column |
| `components/ui/data-table.tsx` | Generic wrapper |

Nested data access (eager-loaded relations) uses the row accessor inside a custom `cell`:

```typescript
{
    header: 'City',
    cell: ({ row }) => row.original.address?.city ?? '—',
}
```

Use `row.original` for nested fields rather than dotted accessor keys.

---

## File uploads

Drag-and-drop file upload uses `FileUploader` from `components/ui/`. Form data submits multipart automatically through `useForm`.

| Field | Form key | Backend column |
|-------|----------|----------------|
| Player photo | `profile_photo` | `players.profile_photo_url` |
| Club logo | `logo` | `clubs.logo_url` |
| Tournament logo | `logo` | `tournaments.logo_url` |

The controller's `FileUpload` trait stores the file and writes the path to the matching `*_url` column. Always `unset()` the file key from the validated array before passing it to the repository.

---

## Date handling

`date-fns` 3.6 is the project's date library. Avoid `moment`, `dayjs`, `luxon`.

DOB and date inputs use a shadcn Calendar inside a Popover. Format selection to `yyyy-MM-dd` before storing in form state.

---

## Building & dev server

```bash
npm run dev       # Vite dev server (HMR)
npm run build     # Production build
npm run build:ssr # Production build with SSR
composer dev      # All-in-one: serve + queue + pail + vite
composer dev:ssr  # Same, with SSR
```

If the user reports the UI hasn't updated, the dev server is likely not running — ask them to start it.

---

## Code quality before commit

```bash
npm run types         # tsc — strict mode, zero errors expected
npm run lint          # ESLint auto-fix
npm run format        # Prettier
```

Run these before considering a frontend task done.
