# Frontend Overview

React 19 / TypeScript 5.7 frontend served via Inertia.js 2 and bundled with Vite 6. All source lives under `resources/js/`. The backend renders the initial HTML shell (SSR or classic Laravel blade stub); Inertia drives subsequent navigations as a single-page app.

---

## Tech Stack

| Layer | Library / Version | Notes |
|---|---|---|
| UI framework | React 19 | Concurrent features, `createRoot` |
| Language | TypeScript 5.7 strict | `strict: true`, path alias `@/` |
| Bundler | Vite 6 | HMR in dev, `laravel-vite-plugin` |
| CSS framework | Tailwind CSS 4 | Utility-first; mandatory `dark:` variants |
| Component library | shadcn/ui (Radix UI) | Headless primitives in `components/ui/` |
| Data tables | TanStack Table 8 | Used across all list pages |
| Routing / SPA bridge | Inertia.js 2 | `@inertiajs/react` |
| Type-safe routes | Ziggy 2.5 | `route()` helper, global type in `types/global.d.ts` |
| Toast notifications | sonner | `<Toaster>` in AppLayout, driven by `useFlashToast` |
| Date formatting | date-fns | Via `utils/date.tsx` helpers |
| Icons | lucide-react | Used throughout pages and nav |

---

## Directory Structure

```
resources/js/
├── app.tsx                         # CSR entry — createInertiaApp + initializeTheme
├── ssr.tsx                         # SSR entry — createServer + renderToString
│
├── components/
│   ├── app-content.tsx
│   ├── app-header.tsx
│   ├── app-logo-icon.tsx
│   ├── app-logo.tsx
│   ├── app-shell.tsx
│   ├── app-sidebar-header.tsx
│   ├── app-sidebar.tsx
│   ├── appearance-dropdown.tsx
│   ├── appearance-tabs.tsx
│   ├── breadcrumbs.tsx
│   ├── delete-user.tsx
│   ├── heading-small.tsx
│   ├── heading.tsx
│   ├── icon.tsx
│   ├── input-error.tsx
│   ├── nav-footer.tsx
│   ├── nav-main.tsx
│   ├── nav-user.tsx
│   ├── text-link.tsx
│   ├── user-info.tsx
│   ├── user-menu-content.tsx
│   ├── brackets/
│   │   ├── bracket-container.tsx
│   │   ├── match-card.tsx
│   │   ├── match-detail-dialog.tsx
│   │   └── single-elimination-bracket.tsx
│   ├── svg/
│   │   ├── sort-ascending.tsx
│   │   └── sort-descending.tsx
│   └── ui/                         # shadcn/ui primitives
│       ├── alert.tsx
│       ├── avatar.tsx
│       ├── badge.tsx
│       ├── breadcrumb.tsx
│       ├── button.tsx
│       ├── calendar.tsx
│       ├── card.tsx
│       ├── checkbox.tsx
│       ├── collapsible.tsx
│       ├── data-table-controls.tsx
│       ├── data-table-pagination.tsx
│       ├── data-table.tsx
│       ├── date-picker.tsx
│       ├── dialog.tsx
│       ├── dropdown-menu.tsx
│       ├── file-uploader.tsx
│       ├── icon.tsx
│       ├── input.tsx
│       ├── label.tsx
│       ├── navigation-menu.tsx
│       ├── placeholder-pattern.tsx
│       ├── popover.tsx
│       ├── radio-group.tsx
│       ├── select.tsx
│       ├── separator.tsx
│       ├── sheet.tsx
│       ├── sidebar.tsx
│       ├── skeleton.tsx
│       ├── table.tsx
│       ├── textarea.tsx
│       ├── toggle-group.tsx
│       ├── toggle.tsx
│       └── tooltip.tsx
│
├── hooks/
│   ├── use-appearance.tsx          # Dark/light/system theme state + initializeTheme()
│   ├── use-flash-toast.ts          # Maps Inertia flash props → sonner toasts
│   ├── use-initials.tsx            # Derives initials from a full name string
│   ├── use-mobile-navigation.ts    # Clears pointer-events lock on mobile nav close
│   └── use-mobile.tsx              # Reactive breakpoint (< 768 px)
│
├── layouts/
│   ├── app-layout.tsx              # Main app layout — AppSidebarLayout + Toaster
│   ├── auth-layout.tsx             # Auth layout — delegates to AuthSplitLayout
│   ├── app/
│   │   ├── app-header-layout.tsx   # Header-variant shell (not used by default pages)
│   │   └── app-sidebar-layout.tsx  # Sidebar-variant shell (used by app-layout.tsx)
│   ├── auth/
│   │   ├── auth-card-layout.tsx    # Centered card on muted background
│   │   ├── auth-simple-layout.tsx  # Minimal centered form, no split panel
│   │   └── auth-split-layout.tsx   # Two-column: dark panel left + form right
│   ├── category/
│   │   └── layout.tsx              # Sub-nav for tournament category pages
│   ├── club/
│   │   └── layout.tsx              # Sub-nav for club pages
│   ├── settings/
│   │   └── layout.tsx              # Sub-nav for settings pages
│   ├── tournament/
│   │   └── layout.tsx              # Sub-nav for tournament pages
│   └── venue/
│       └── layout.tsx              # Sub-nav for venue pages
│
├── lib/
│   └── utils.ts                    # cn() helper — clsx + tailwind-merge
│
├── pages/                          # Inertia page components (file = route)
│   ├── auth/
│   │   ├── confirm-password.tsx
│   │   ├── forgot-password.tsx
│   │   ├── login.tsx
│   │   ├── register.tsx
│   │   ├── reset-password.tsx
│   │   └── verify-email.tsx
│   ├── clubs/
│   │   ├── coaches/
│   │   │   ├── index.tsx
│   │   │   └── partials/columns.tsx
│   │   ├── players/
│   │   │   ├── index.tsx
│   │   │   └── partials/columns.tsx
│   │   ├── partials/columns.tsx
│   │   ├── create.tsx
│   │   ├── index.tsx
│   │   └── show.tsx
│   ├── coaches/
│   │   ├── partials/columns.tsx
│   │   ├── create.tsx
│   │   ├── edit.tsx
│   │   └── index.tsx
│   ├── dashboard.tsx
│   ├── players/
│   │   ├── partials/columns.tsx
│   │   ├── create.tsx
│   │   ├── edit.tsx
│   │   └── index.tsx
│   ├── referees/
│   │   ├── partials/columns.tsx
│   │   ├── create.tsx
│   │   ├── edit.tsx
│   │   └── index.tsx
│   ├── settings/
│   │   ├── appearance.tsx
│   │   ├── password.tsx
│   │   └── profile.tsx
│   ├── tournaments/
│   │   ├── categories/
│   │   │   ├── create.tsx
│   │   │   ├── edit.tsx
│   │   │   └── index.tsx
│   │   ├── matches/
│   │   │   └── index.tsx
│   │   ├── registrations/
│   │   │   └── index.tsx
│   │   ├── results/
│   │   │   └── index.tsx
│   │   ├── partials/
│   │   │   ├── category-columns.tsx
│   │   │   ├── columns.tsx
│   │   │   └── status-badge.tsx
│   │   ├── create.tsx
│   │   ├── index.tsx
│   │   └── show.tsx
│   ├── venues/
│   │   ├── mats/
│   │   │   └── index.tsx
│   │   ├── partials/columns.tsx
│   │   ├── create.tsx
│   │   ├── edit.tsx
│   │   ├── index.tsx
│   │   └── show.tsx
│   └── welcome.tsx
│
├── types/
│   ├── global.d.ts                 # global route() type from Ziggy
│   ├── index.d.ts                  # Shared interfaces: SharedData, User, etc.
│   └── vite-env.d.ts               # import.meta.env types
│
└── utils/
    ├── countries.tsx               # Static Nepal province/district data
    └── date.tsx                    # UTC ↔ local date formatting helpers
```

---

## Entry Points

### `app.tsx` — CSR + SSR hydration

The browser entry point. Loaded by the Laravel blade template via the `@vite` directive. Calls `createInertiaApp` to mount the React tree into the server-rendered (or blank) DOM element, then calls `initializeTheme()` at module level so the correct colour scheme is applied before the first React paint. See [Bootstrap](Bootstrap.md) for the full flow.

### `ssr.tsx` — SSR render

Runs in Node.js under `@inertiajs/react/server`. Uses `ReactDOMServer.renderToString` to produce the initial HTML. Ziggy's `route()` helper is polyfilled onto the Node `global` object so page components can call it during server render. See [Bootstrap](Bootstrap.md).

---

## Page Resolution

Inertia resolves page names to file paths with a glob import:

```ts
resolve: (name) =>
    resolvePageComponent(
        `./pages/${name}.tsx`,
        import.meta.glob('./pages/**/*.tsx'),
    ),
```

The backend passes a page component name as a string (e.g. `'venues/index'`). Inertia looks up `resources/js/pages/venues/index.tsx`. The naming convention is:

- All lowercase, forward-slash separated
- Matches the filesystem path under `resources/js/pages/`
- Examples:
  - `'dashboard'` → `pages/dashboard.tsx`
  - `'tournaments/show'` → `pages/tournaments/show.tsx`
  - `'tournaments/categories/edit'` → `pages/tournaments/categories/edit.tsx`
  - `'auth/login'` → `pages/auth/login.tsx`

---

## Dark Mode

Tailwind 4 dark mode is class-based (`dark` on `<html>`). The rule is: **every new page must include `dark:` variants** for all colour utilities. The `initializeTheme()` function (called before React mounts) reads `localStorage.appearance` and applies the class immediately, eliminating flash-of-wrong-theme.

---

## Path Alias

`@/` is configured in `vite.config.ts` and `tsconfig.json` to resolve to `resources/js/`. Use it for all cross-directory imports:

```ts
import { cn } from '@/lib/utils';
import { useFlashToast } from '@/hooks/use-flash-toast';
import type { SharedData } from '@/types';
```

Never use relative `../../` paths across major directory boundaries.

---

## TypeScript Strict Mode

`tsconfig.json` enables `strict: true`. This means:

- `noImplicitAny` — all parameters and return types must be explicit or inferrable
- `strictNullChecks` — `null` and `undefined` are not assignable without explicit union
- `strictFunctionTypes`, `strictBindCallApply`, etc.

Module-specific types (Player, Tournament, Venue, etc.) are defined co-located in their page files, not centrally. Only truly shared types live in `types/index.d.ts`.

---

## Child Wiki Docs

- [Bootstrap](Bootstrap.md) — `app.tsx` and `ssr.tsx` in detail
- [Hooks](../Hooks/Index.md) — all 5 custom hooks
- [Types](../Types/Index.md) — TypeScript type system
- [Utils](../Utils/Index.md) — date helpers, countries data, `cn()`
- [Layouts](../Layouts/Index.md) — all layout components
- [Components](../Components/Index.md) — shared UI components
- [Pages](../Pages/Index.md) — page component catalogue
