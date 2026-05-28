# Layouts

All layout files live under `resources/js/layouts/`. There are two top-level layouts (`app-layout.tsx`, `auth-layout.tsx`), their underlying implementations (`app/`, `auth/`), and five domain sub-layouts that provide sidebar-with-nav for entity detail pages.

---

## Layout Hierarchy

```
app-layout.tsx              → app/app-sidebar-layout.tsx
                                → AppShell (sidebar) + AppSidebar + AppContent + AppSidebarHeader

auth-layout.tsx             → auth/auth-split-layout.tsx

app/app-header-layout.tsx   (alternative app shell, not the default)

auth/auth-card-layout.tsx   (card variant, currently unused as default)
auth/auth-simple-layout.tsx (minimal variant)
auth/auth-split-layout.tsx  (used by auth-layout.tsx)

layouts/tournament/layout.tsx
layouts/category/layout.tsx
layouts/venue/layout.tsx
layouts/club/layout.tsx
layouts/settings/layout.tsx
```

---

## `app-layout.tsx` — Main Application Layout

**File:** `resources/js/layouts/app-layout.tsx`

### Purpose

The shell for all authenticated (non-auth) pages. Provides the sidebar navigation, breadcrumb header, appearance-aware toasts, and the flash message bridge.

### Props

```ts
interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}
```

### What it renders

```tsx
const { appearance } = useAppearance();
useFlashToast();

return (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        {children}
        <Toaster position="top-right" richColors closeButton theme={appearance} />
    </AppLayoutTemplate>
);
```

- Calls `useAppearance()` to read the current theme and pass it to `<Toaster theme={appearance}>` so sonner's toast styling (background, text colour) matches the active dark/light theme.
- Calls `useFlashToast()` to automatically display server flash messages as toasts after every Inertia navigation.
- Delegates to `AppLayoutTemplate` (alias for `app/app-sidebar-layout.tsx`).
- Places `<Toaster>` as a sibling to `{children}` inside the template — it renders at the top-right of the viewport.

### Pages that use it

All authenticated pages import and use `AppLayout`:

```tsx
import AppLayout from '@/layouts/app-layout';

export default function DashboardPage() {
    return (
        <AppLayout breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}>
            {/* page content */}
        </AppLayout>
    );
}
```

---

## `app/app-sidebar-layout.tsx` — Sidebar Shell

**File:** `resources/js/layouts/app/app-sidebar-layout.tsx`

### Purpose

The concrete implementation of the sidebar-variant application shell. `app-layout.tsx` delegates entirely to this component.

### Props

```ts
{ children: ReactNode; breadcrumbs?: BreadcrumbItem[] }
```

Default for `breadcrumbs` is `[]`.

### What it renders

```tsx
<AppShell variant="sidebar">
    <AppSidebar />
    <AppContent variant="sidebar">
        <AppSidebarHeader breadcrumbs={breadcrumbs} />
        {children}
    </AppContent>
</AppShell>
```

- `AppShell` — root flex container; `variant="sidebar"` sets the layout to sidebar-adjacent content.
- `AppSidebar` — the navigation sidebar containing logo, `NavMain`, user menu, and footer links.
- `AppContent` — the scrollable main content area.
- `AppSidebarHeader` — top bar within the content area; shows the mobile sidebar trigger, breadcrumbs, and user actions.

---

## `app/app-header-layout.tsx` — Header Shell (Alternative)

**File:** `resources/js/layouts/app/app-header-layout.tsx`

### Purpose

An alternative application shell that uses a top navigation bar instead of a sidebar. Not used by the default `app-layout.tsx` but available for pages that want a header-only layout.

### Props

```ts
{ children: ReactNode; breadcrumbs?: BreadcrumbItem[] }
```

### What it renders

```tsx
<AppShell>
    <AppHeader breadcrumbs={breadcrumbs} />
    <AppContent>{children}</AppContent>
</AppShell>
```

`AppShell` without `variant="sidebar"` renders a vertical stack (header on top, content below).

---

## `auth-layout.tsx` — Auth Layout

**File:** `resources/js/layouts/auth-layout.tsx`

### Purpose

The top-level layout for all authentication pages. A thin pass-through that delegates to `AuthSplitLayout`.

### Props

```ts
{
    children: React.ReactNode;
    title: string;
    description: string;
}
```

### What it renders

```tsx
<AuthLayoutTemplate title={title} description={description} {...props}>
    {children}
</AuthLayoutTemplate>
```

`AuthLayoutTemplate` is `auth/auth-split-layout.tsx`.

### Pages that use it

All pages under `pages/auth/`: login, register, forgot-password, reset-password, confirm-password, verify-email.

---

## `auth/auth-split-layout.tsx` — Two-Column Auth Layout

**File:** `resources/js/layouts/auth/auth-split-layout.tsx`

### Purpose

The default auth layout. On large screens it shows a dark left panel with the app logo and an inspirational quote, and the auth form on the right. On mobile the left panel is hidden.

### Props

```ts
interface AuthLayoutProps {
    title?: string;
    description?: string;
}
```

Plus `children` via `PropsWithChildren`.

### What it renders

- **Left panel** (hidden on `< lg`): dark zinc-900 background, app logo link, `SharedData.name` as brand name, `SharedData.quote.message` and `SharedData.quote.author` as a blockquote.
- **Right panel**: centred form area, `AppLogoIcon` link (visible only on mobile), `title` as `<h1>`, `description` as `<p>`, then `{children}`.

Reads `name` and `quote` from `usePage<SharedData>().props` — both are injected by the Laravel backend's `HandleInertiaRequests` middleware.

---

## `auth/auth-card-layout.tsx` — Card Auth Layout

**File:** `resources/js/layouts/auth/auth-card-layout.tsx`

### Purpose

An alternative auth layout with the form inside a `Card` component on a muted background. Available for use but not wired to `auth-layout.tsx` by default.

### Props

```ts
{
    name?: string;
    title?: string;
    description?: string;
}
```

### What it renders

- Full-screen muted background (`bg-muted min-h-svh`).
- App logo link centred above the card.
- A `Card` component with `CardHeader` (title + description) and `CardContent` (children).
- Maximum width `max-w-md`.

---

## `auth/auth-simple-layout.tsx` — Minimal Auth Layout

**File:** `resources/js/layouts/auth/auth-simple-layout.tsx`

### Purpose

The simplest auth layout — no split panel, no card borders. Form floats centred on the default background. Used for very minimal pages (e.g. email verification notice).

### Props

```ts
interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
}
```

### What it renders

- Full-screen `bg-background min-h-svh`.
- Logo icon link → screen-reader-only title text.
- `<h1>` title + `<p>` description.
- `{children}` below.

---

## `layouts/tournament/layout.tsx` — Tournament Sub-Layout

**File:** `resources/js/layouts/tournament/layout.tsx`

### Purpose

Sidebar-with-nav layout for the tournament detail section. All pages related to a specific tournament (overview, categories) are wrapped in this layout to provide consistent navigation and the tournament header.

### Props

```ts
interface TournamentLayoutProps {
    tournament: Tournament;
    activeKey: 'overview' | 'categories';
}
```

`Tournament` is imported from `@/pages/tournaments`.

### SSR guard

```ts
if (typeof window === 'undefined') return null;
```

Returns null during SSR because it reads `window` indirectly via the `cn` function and builds href strings. The page will render correctly after hydration.

### What it renders

**Header:**
- `Avatar` (14×14) with `tournament.logo_url` image or initials fallback (first two word initials of `tournament.name`).
- Tournament name as `<h1>`.
- Level badge — mapped from numeric `tournament.level`: `1=Local`, `2=Regional`, `3=National`, `4=International`.
- Status badge — mapped from numeric `tournament.status`: `0=Scheduled`, `1=In Progress`, `2=Completed`, `3=Cancelled`.
- Description (if present).

**Sidebar nav (256 px wide on `lg+`):**

| Key | Label | Route |
|---|---|---|
| `overview` | Overview | `/manage/tournaments/{id}` |
| `categories` | Categories | `/manage/tournaments/{id}/categories` |

Active item gets `bg-muted`. Links use Inertia `<Link prefetch>`.

**Content area:** `flex-1` section with `space-y-12`.

### Pages that use it

- `pages/tournaments/show.tsx`
- `pages/tournaments/categories/index.tsx`

---

## `layouts/category/layout.tsx` — Tournament Category Sub-Layout

**File:** `resources/js/layouts/category/layout.tsx`

### Purpose

Sidebar-with-nav layout for pages within a tournament category (edit, registrations, matches, results).

### Props

```ts
interface CategoryLayoutProps {
    tournament: Tournament;
    category: TournamentCategory;
    activeKey: 'overview' | 'registrations' | 'matches' | 'results';
}
```

Both `Tournament` and `TournamentCategory` are imported from `@/pages/tournaments`.

### SSR guard

```ts
if (typeof window === 'undefined') return null;
```

### What it renders

**Header:**
- Display name via `getCategoryDisplayName(category)` (imported from `pages/tournaments/partials/category-columns.tsx`).
- Status badge — `0=Pending`, `1=Seeding`, `2=Active`, `3=Completed`.
- Subtitle: `{tournament.name} · Age {min_age}–{max_age} yrs · Weight {min_weight}–{max_weight} kg`.

**Sidebar nav:**

| Key | Label | Route |
|---|---|---|
| `overview` | Overview | `/manage/tournaments/{tid}/categories/{cid}/edit` |
| `registrations` | Registrations | `/manage/tournaments/{tid}/categories/{cid}/registrations` |
| `matches` | Matches | `/manage/tournaments/{tid}/categories/{cid}/matches` |
| `results` | Results | `/manage/tournaments/{tid}/categories/{cid}/results` |

Note: the Overview key navigates to the **edit** page (not a read-only show page).

### Pages that use it

- `pages/tournaments/categories/edit.tsx`
- `pages/tournaments/registrations/index.tsx`
- `pages/tournaments/matches/index.tsx`
- `pages/tournaments/results/index.tsx`

---

## `layouts/venue/layout.tsx` — Venue Sub-Layout

**File:** `resources/js/layouts/venue/layout.tsx`

### Purpose

Sidebar-with-nav layout for venue detail pages (overview, mats).

### Props

```ts
interface VenueLayoutProps {
    venue: Venue;
    activeKey: 'overview' | 'mats';
}
```

`Venue` is imported from `@/pages/venues/index`.

### SSR guard

```ts
if (typeof window === 'undefined') return null;
```

### What it renders

**Header:**
- Venue name as `<h1>` with `dark:text-white`.
- Location line: `<MapPin>` icon + `{city}, {country.name ?? country_code}` (filtered for non-null parts).

**Sidebar nav:**

| Key | Label | Route |
|---|---|---|
| `overview` | Overview | `/manage/venues/{id}` |
| `mats` | Mats | `/manage/venues/{id}/mats` |

### Pages that use it

- `pages/venues/show.tsx`
- `pages/venues/mats/index.tsx`

---

## `layouts/club/layout.tsx` — Club Sub-Layout

**File:** `resources/js/layouts/club/layout.tsx`

### Purpose

Sidebar-with-nav layout for club detail pages (overview, players, coaches).

### Props

```ts
interface ClubLayoutProps {
    club: Club;
    activeKey: 'overview' | 'players' | 'coaches';
}
```

`Club` is imported from `@/pages/clubs`.

### SSR guard

```ts
if (typeof window === 'undefined') return null;
```

### What it renders

**Header:**
- `Avatar` (14×14) with `club.logo_url` image or initials fallback (first two word initials).
- Club name as `<h1>`.
- `club.abbreviation` shown inline if present (muted, small font).
- Address line: `address`, `city`, `state`, `country` joined by `, ` (falsy parts filtered out).

**Sidebar nav:**

| Key | Label | Route |
|---|---|---|
| `overview` | Overview | `/manage/clubs/{id}` |
| `players` | Players | `/manage/clubs/{id}/players` |
| `coaches` | Coaches | `/manage/clubs/{id}/coaches` |

### Pages that use it

- `pages/clubs/show.tsx`
- `pages/clubs/players/index.tsx`
- `pages/clubs/coaches/index.tsx`

---

## `layouts/settings/layout.tsx` — Settings Sub-Layout

**File:** `resources/js/layouts/settings/layout.tsx`

### Purpose

Sidebar-with-nav layout for user settings pages. Unlike the domain layouts, this one has a fixed nav (no entity prop) and determines the active item by comparing `window.location.pathname` to each nav item's `href`.

### Props

```ts
PropsWithChildren  (no additional props)
```

### SSR guard

```ts
if (typeof window === 'undefined') return null;
```

Comment in source: "When server-side rendering, we only render the layout on the client..."

### What it renders

**Header:**
- `<Heading title="Settings" description="Manage your profile and account settings" />`.

**Sidebar nav (192 px wide on `lg+`):**

| Title | href |
|---|---|
| Profile | `/settings/profile` |
| Password | `/settings/password` |
| Appearance | `/settings/appearance` |

Active item is determined by `currentPath === item.href` (exact match on `window.location.pathname`), not an `activeKey` prop. This makes it self-contained — pages do not need to pass anything other than `children`.

**Content area:** `flex-1 md:max-w-2xl` with `max-w-xl space-y-12` on the inner section.

### Pages that use it

- `pages/settings/profile.tsx`
- `pages/settings/password.tsx`
- `pages/settings/appearance.tsx`

---

## Shared Layout Patterns

All five domain sub-layouts follow the same structural pattern:

1. **SSR guard** — `if (typeof window === 'undefined') return null` at the top of the render function.
2. **Header** — entity-specific title/avatar/badges above the nav + content row.
3. **Two-column flex** — `flex-col` on mobile, `lg:flex-row` on desktop.
4. **Aside (nav)** — `lg:w-56` (or `lg:w-48` for settings), ghost buttons, `bg-muted` on active.
5. **Mobile separator** — `<Separator className="my-6 md:hidden" />` between nav and content on mobile.
6. **Content** — `flex-1 section space-y-12`.
7. **Inertia `<Link prefetch>`** — all nav links use Inertia prefetch for instant navigation.

---

## Related

- [Frontend Overview](../Frontend/Index.md)
- [Hooks — useAppearance, useFlashToast](../Hooks/Index.md)
- [Types — BreadcrumbItem, SharedData](../Types/Index.md)
- [Components](../Components/Index.md)
