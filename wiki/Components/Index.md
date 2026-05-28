# Component Index

All React components in `resources/js/components/`. Path alias `@/` maps to `resources/js/`. Use `cn` from `@/lib/utils` to merge Tailwind classes. All components support dark mode via `dark:` variants.

---

## App-Level Components

These components assemble the two application shells (header-based and sidebar-based).

| File | Purpose | Key Props |
|------|---------|-----------|
| `components/app-shell.tsx` | Root layout wrapper — provides `SidebarProvider` for the sidebar variant or a plain flex column for the header variant | `children`, `variant?: 'header' \| 'sidebar'` (default `'header'`) |
| `components/app-sidebar.tsx` | Floating, icon-collapsible sidebar with logo, main nav, footer nav, and user section | none — self-contained, reads `SharedData` via Inertia |
| `components/app-sidebar-header.tsx` | Sticky page header rendered inside the sidebar layout; contains the sidebar toggle trigger and breadcrumbs | `breadcrumbs?: BreadcrumbItem[]` |
| `components/app-header.tsx` | Full top-navigation bar for the header layout; includes desktop nav links, mobile Sheet drawer, search button, right-side icon links, and user avatar dropdown | `breadcrumbs?: BreadcrumbItem[]` |
| `components/app-content.tsx` | Content area wrapper; renders as `SidebarInset` for sidebar variant or a max-w-7xl centered `<main>` for header variant | `variant?: 'header' \| 'sidebar'`, plus all `<main>` props |
| `components/app-logo.tsx` | Logo block: square icon container + "DOJOHUB" wordmark; used in sidebar and header | none |
| `components/app-logo-icon.tsx` | Raw SVG of the DojoHub karate figure icon | `SVGAttributes<SVGElement>` (size, className, etc.) |
| `components/nav-main.tsx` | Primary sidebar navigation group; marks the active item by comparing `item.href` to `page.url` | `items: NavItem[]` |
| `components/nav-footer.tsx` | Sidebar footer navigation group; renders items as external links (`target="_blank"`) | `items: NavItem[]`, plus `SidebarGroup` props (e.g. `className`) |
| `components/nav-user.tsx` | Sidebar user row at the bottom; opens a `DropdownMenu` with `UserMenuContent` on click; adapts side based on mobile/collapsed state | none — reads `auth.user` from `SharedData` |
| `components/breadcrumbs.tsx` | Renders a `BreadcrumbItem[]` array as a shadcn Breadcrumb; last item is non-linked `BreadcrumbPage`, others are Inertia `<Link>` | `breadcrumbs: BreadcrumbItem[]` |
| `components/heading.tsx` | Page-level section heading with optional description | `title: string`, `description?: string` |
| `components/heading-small.tsx` | Subsection heading (`<h3>`) with optional description; renders inside a `<header>` element | `title: string`, `description?: string` |
| `components/icon.tsx` | Thin wrapper that renders a Lucide icon component with default `h-4 w-4` sizing | `iconNode: ComponentType<LucideProps>`, plus all `LucideProps` (e.g. `className`) |
| `components/input-error.tsx` | Renders a validation error message in red below a form field; renders nothing when `message` is falsy | `message?: string`, plus `HTMLAttributes<HTMLParagraphElement>` |
| `components/text-link.tsx` | Styled Inertia `<Link>` with underline animation on hover | All `Link` props from `@inertiajs/react` |
| `components/user-info.tsx` | Avatar + name (+ optional email) layout used inside dropdown menus and the sidebar user row | `user: User`, `showEmail?: boolean` (default `false`) |
| `components/user-menu-content.tsx` | Dropdown menu content for the user avatar menu: user info label, Settings link, Logout button | `user: User` |
| `components/appearance-dropdown.tsx` | Ghost icon button that opens a 3-item dropdown (Light / Dark / System) for theme switching | `className?`, plus `HTMLAttributes<HTMLDivElement>` |
| `components/appearance-tabs.tsx` | Inline segmented-control (Light / Dark / System) for theme switching; used on the Settings appearance page | `className?`, plus `HTMLAttributes<HTMLDivElement>` |
| `components/delete-user.tsx` | Self-contained "Delete account" section with a red warning box and a Dialog that requires password confirmation before deletion | none |

---

## UI Components (`components/ui/`)

Shadcn/Radix-based primitive components. All accept `className` for overrides.

### Data & Tables

| File | Purpose | Key Props / Exports |
|------|---------|---------------------|
| `ui/data-table.tsx` | TanStack Table wrapper; renders a bordered table with empty-state row | `columns: ColumnDef<TData, TValue>[]`, `data: TData[]`, `onRowClick?: (row: TData) => void` |
| `ui/data-table-controls.tsx` | Toolbar above a DataTable: keyword search input, sort-by dropdown, asc/desc toggle group, optional status filter dropdown | See `DataTableControlsProps` below |
| `ui/data-table-pagination.tsx` | Laravel paginator link renderer with per-page selector; hides prev/next buttons when only one page | `links: PaginationLink[]`, `perPage: number`, `perPageSelect?: number[]`, `onPageChange`, `onPerPageCountUpdated` |
| `ui/table.tsx` | Low-level `<table>` primitives: `Table`, `TableHeader`, `TableBody`, `TableFooter`, `TableHead`, `TableRow`, `TableCell`, `TableCaption` | Each accepts standard HTML element props |
| `ui/date-picker.tsx` | Popover-based date picker wrapping `Calendar`; value is an ISO date string (`yyyy-MM-dd`) | `value: string`, `onChange: (value: string) => void`, `placeholder?`, `className?`, `id?` |
| `ui/file-uploader.tsx` | Drag-and-drop / click-to-upload file input with image preview, size validation, and remove button | `accept?`, `maxSize?` (bytes), `onFileSelect: (file: File \| null) => void`, `initialPreview?`, `className?` |

### Form Inputs

| File | Purpose | Key Props / Exports |
|------|---------|---------------------|
| `ui/button.tsx` | CVA-based button; variants: `default`, `destructive`, `outline`, `secondary`, `ghost`, `link`; sizes: `default`, `sm`, `lg`, `icon` | `variant?`, `size?`, `asChild?: boolean` |
| `ui/input.tsx` | Styled `<input>` element | All `React.ComponentProps<"input">` |
| `ui/label.tsx` | Radix Label; disables on peer/group disabled state | All `LabelPrimitive.Root` props |
| `ui/textarea.tsx` | Auto-sizing `<textarea>` with field-sizing-content | All `React.ComponentProps<"textarea">` |
| `ui/select.tsx` | Radix Select; exports `Select`, `SelectTrigger`, `SelectContent`, `SelectItem`, `SelectLabel`, `SelectGroup`, `SelectValue`, `SelectSeparator`, scroll buttons | Radix Select props per sub-component |
| `ui/checkbox.tsx` | Radix Checkbox with checkmark icon | All `CheckboxPrimitive.Root` props |
| `ui/radio-group.tsx` | Radix RadioGroup; exports `RadioGroup`, `RadioGroupItem` | Radix RadioGroup props |
| `ui/calendar.tsx` | `react-day-picker` DayPicker wrapper styled with Tailwind; supports single/range mode | All `DayPicker` props (e.g. `mode`, `selected`, `onSelect`) |

### Overlay & Disclosure

| File | Purpose | Key Props / Exports |
|------|---------|---------------------|
| `ui/dialog.tsx` | Radix Dialog; exports `Dialog`, `DialogTrigger`, `DialogContent`, `DialogHeader`, `DialogFooter`, `DialogTitle`, `DialogDescription`, `DialogClose` | Radix Dialog props per sub-component |
| `ui/popover.tsx` | Radix Popover; exports `Popover`, `PopoverTrigger`, `PopoverContent`, `PopoverAnchor` | `align?`, `sideOffset?` on `PopoverContent` |
| `ui/dropdown-menu.tsx` | Radix DropdownMenu; exports the full primitive set including checkbox items, radio items, sub-menu, shortcut | Radix DropdownMenu props per sub-component |
| `ui/sheet.tsx` | Radix Dialog re-skinned as a side-panel; exports `Sheet`, `SheetTrigger`, `SheetContent`, `SheetHeader`, `SheetFooter`, `SheetTitle`, `SheetDescription` | `side?: 'top' \| 'right' \| 'bottom' \| 'left'` on `SheetContent` |
| `ui/collapsible.tsx` | Radix Collapsible; exports `Collapsible`, `CollapsibleTrigger`, `CollapsibleContent` | Radix Collapsible props |
| `ui/tooltip.tsx` | Radix Tooltip; `Tooltip` wraps `TooltipProvider` automatically; exports `Tooltip`, `TooltipTrigger`, `TooltipContent`, `TooltipProvider` | `sideOffset?` (default 4) on `TooltipContent` |

### Display

| File | Purpose | Key Props / Exports |
|------|---------|---------------------|
| `ui/avatar.tsx` | Radix Avatar; exports `Avatar`, `AvatarImage`, `AvatarFallback` | Radix Avatar props per sub-component |
| `ui/badge.tsx` | Inline label chip; variants: `default`, `secondary`, `destructive`, `outline`; supports `asChild` | `variant?`, `asChild?: boolean` |
| `ui/card.tsx` | Card container; exports `Card`, `CardHeader`, `CardTitle`, `CardDescription`, `CardContent`, `CardFooter` | Standard div props per sub-component |
| `ui/skeleton.tsx` | Animated pulse placeholder block | All div props |
| `ui/alert.tsx` | Alert banner; variants: `default`, `destructive`; exports `Alert`, `AlertTitle`, `AlertDescription` | `variant?` |
| `ui/separator.tsx` | Radix Separator; horizontal or vertical rule | `orientation?: 'horizontal' \| 'vertical'`, `decorative?` |
| `ui/placeholder-pattern.tsx` | SVG hatching pattern used as an empty-state background texture | `className?` |

### Navigation

| File | Purpose | Key Props / Exports |
|------|---------|---------------------|
| `ui/breadcrumb.tsx` | Radix-style breadcrumb primitives: `Breadcrumb`, `BreadcrumbList`, `BreadcrumbItem`, `BreadcrumbLink`, `BreadcrumbPage`, `BreadcrumbSeparator`, `BreadcrumbEllipsis` | Standard HTML element props per sub-component |
| `ui/navigation-menu.tsx` | Radix NavigationMenu; exports full primitive set including `navigationMenuTriggerStyle` CVA function | `viewport?: boolean` on `NavigationMenu` |
| `ui/sidebar.tsx` | Full shadcn Sidebar implementation; `SidebarProvider` stores open state in a cookie; exports `Sidebar`, `SidebarContent`, `SidebarHeader`, `SidebarFooter`, `SidebarMenu`, `SidebarMenuItem`, `SidebarMenuButton`, `SidebarGroup`, `SidebarGroupContent`, `SidebarInset`, `SidebarTrigger`, `useSidebar` and more | `defaultOpen?` on `SidebarProvider`; `collapsible?`, `variant?` on `Sidebar` |

### Toggle

| File | Purpose | Key Props / Exports |
|------|---------|---------------------|
| `ui/toggle.tsx` | Radix Toggle; variants: `default`, `outline`; sizes: `default`, `sm`, `lg` | `variant?`, `size?` |
| `ui/toggle-group.tsx` | Radix ToggleGroup; shares variant/size context with child `ToggleGroupItem` | `type: 'single' \| 'multiple'`, `variant?`, `size?` |

### Icon (ui/)

| File | Purpose | Key Props |
|------|---------|-----------|
| `ui/icon.tsx` | Null-safe Lucide icon renderer (no default sizing, unlike `components/icon.tsx`) | `iconNode?: LucideIcon \| null`, `className?` |

---

## Bracket Components (`components/brackets/`)

Components for rendering tournament bracket views.

| File | Purpose | Key Props |
|------|---------|-----------|
| `brackets/bracket-container.tsx` | Dispatch component: switches on `bracket.type` and renders the appropriate bracket layout | `bracket: BracketStructure`, `onMatchClick?: (matchId: string) => void`, `viewOnly?: boolean` |
| `brackets/single-elimination-bracket.tsx` | Renders rounds side-by-side with exponentially increasing gaps; each match is a `MatchCard` | `rounds: BracketRound[]`, `onMatchClick?`, `viewOnly?` |
| `brackets/match-card.tsx` | Card showing two competitors with avatars, seeds, scores, winner trophy, scheduled time, and status badge | `match: Match`, `onClick?`, `viewOnly?` |
| `brackets/match-detail-dialog.tsx` | Dialog for scheduling a match and recording its result (winner + scores via Inertia PATCH) | `match: Match \| null`, `tournamentId: string`, `open: boolean`, `onOpenChange` |

---

## SVG Components (`components/svg/`)

| File | Purpose | Key Props |
|------|---------|-----------|
| `svg/sort-ascending.tsx` | Sort-ascending icon (arrow down + lines) for use in `DataTableControls` | `size?` (default `"24px"`), `className?` |
| `svg/sort-descending.tsx` | Sort-descending icon (arrow up + lines) for use in `DataTableControls` | `size?` (default `"24px"`), `className?` |
