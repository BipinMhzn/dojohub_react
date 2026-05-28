# AppSidebar

The application sidebar, built on the shadcn `Sidebar` primitive from `components/ui/sidebar.tsx`. Used in all authenticated pages via `AppLayout`.

**Files:**
- `resources/js/components/app-sidebar.tsx` — root sidebar component
- `resources/js/components/app-sidebar-header.tsx` — per-page header bar inside the sidebar layout
- `resources/js/components/nav-main.tsx` — primary navigation group
- `resources/js/components/nav-footer.tsx` — bottom navigation group
- `resources/js/components/nav-user.tsx` — user row with dropdown menu

---

## Sidebar structure

```
<Sidebar collapsible="icon" variant="floating">
  <SidebarHeader>
    Logo link → /dashboard
  </SidebarHeader>

  <SidebarContent>
    <NavMain items={mainNavItems} />
  </SidebarContent>

  <SidebarFooter>
    <NavFooter items={footerNavItems} />
    <NavUser />
  </SidebarFooter>
</Sidebar>
```

The `Sidebar` is configured with:
- `collapsible="icon"` — collapses to icon-only mode (3 rem wide, tooltips on hover)
- `variant="floating"` — floats with rounded corners and a drop shadow rather than pushing page content

---

## sidebarOpen shared prop

`AppShell` reads `sidebarOpen` from the Inertia shared data and passes it as `defaultOpen` to `SidebarProvider`:

```tsx
// app-shell.tsx
const isOpen = usePage<SharedData>().props.sidebarOpen;
return <SidebarProvider defaultOpen={isOpen}>{children}</SidebarProvider>;
```

`SharedData.sidebarOpen: boolean` is set by the Laravel backend (via `HandleInertiaRequests` middleware) and persisted in a cookie (`sidebar_state`, 7-day TTL) by the `SidebarProvider`. The server-side value initialises state on first load; subsequent toggles are stored client-side in the cookie.

---

## Navigation items

Defined as static arrays inside `app-sidebar.tsx`:

### Main nav (`mainNavItems`)

| Title | href | Icon (lucide-react) |
|-------|------|---------------------|
| Dashboard | `/dashboard` | `LayoutGrid` |
| Tournaments | `/manage/tournaments` | `Trophy` |
| Clubs | `/manage/clubs` | `Shield` |
| Players | `/manage/players` | `User` |
| Referees | `/manage/referees` | `Gavel` |
| Coaches | `/manage/coaches` | `Users` |
| Venues | `/manage/venues` | `MapPinHouse` |

### Footer nav (`footerNavItems`)

Currently empty (`[]`). Add external link items here (e.g. documentation, support) — they open in `target="_blank"`.

---

## NavMain

**File:** `resources/js/components/nav-main.tsx`

```ts
// Props
{ items: NavItem[] }
```

Renders a `SidebarGroup` with a `SidebarMenu`. Each item is a `SidebarMenuItem` wrapping a `SidebarMenuButton`:

- `isActive` is set when `item.href === page.url` (exact match via `usePage().url`)
- The button uses `asChild` to render an Inertia `<Link href={item.href} prefetch>`
- In collapsed mode the button shows only the icon; `tooltip={{ children: item.title }}` provides the label on hover

---

## NavFooter

**File:** `resources/js/components/nav-footer.tsx`

```ts
// Props
ComponentPropsWithoutRef<typeof SidebarGroup> & { items: NavItem[] }
```

Similar to `NavMain` but renders items as `<a target="_blank" rel="noopener noreferrer">` (external links). Uses `Icon` from `components/icon.tsx` for rendering. The group has `group-data-[collapsible=icon]:p-0` to collapse padding when the sidebar is in icon mode.

No active-state detection (footer links are external).

---

## NavUser

**File:** `resources/js/components/nav-user.tsx`

No props. Reads `auth.user` from `usePage<SharedData>().props`.

Renders a `SidebarMenuButton` (large size) that triggers a `DropdownMenu` containing `UserMenuContent`. The dropdown `side` is determined by context:
- Mobile: `'bottom'`
- Sidebar collapsed: `'left'` (popout to the right of the icon rail)
- Sidebar expanded: `'bottom'` (drops below the trigger)

The trigger button shows `UserInfo` (avatar + name) and a `ChevronsUpDown` chevron.

---

## AppSidebarHeader

**File:** `resources/js/components/app-sidebar-header.tsx`

```ts
// Props
{ breadcrumbs?: BreadcrumbItem[] }
```

Renders a `<header>` at the top of the page content area (inside `SidebarInset`), containing:

1. `SidebarTrigger` — the hamburger/toggle button that collapses/expands the sidebar (keyboard shortcut `Ctrl/Cmd+B`)
2. `Breadcrumbs` — the breadcrumb trail for the current page

The header adapts its height via `group-has-data-[collapsible=icon]/sidebar-wrapper:h-12` — it becomes slightly shorter when the sidebar is in icon-only mode.

Each page passes its breadcrumb array through `AppLayout`:

```tsx
// app-layout.tsx (layout)
<AppSidebarHeader breadcrumbs={breadcrumbs} />
```

### BreadcrumbItem type

```ts
// resources/js/types/index.d.ts
export interface BreadcrumbItem {
    title: string;
    href: string;
}
```

The last item in the array is rendered as non-linked `BreadcrumbPage`; all preceding items are Inertia `<Link>` breadcrumb links.

---

## NavItem type

```ts
// resources/js/types/index.d.ts
export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;  // not used by NavMain (active state is computed from page.url)
}
```
