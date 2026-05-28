# Coaches Pages — Wiki

## Overview

The Coaches module manages coaching staff who can be assigned to clubs. It has three pages (list, create, edit) plus a columns partial. Coaches are lightweight records — full name plus optional email and phone. Club assignments are managed from the club's Coaches tab (see [Clubs Pages](../Clubs/Index.md)), not from within the Coaches module. The edit page displays active club memberships as read-only badges that link to the relevant club coaches page.

---

## Types

Defined in `resources/js/pages/coaches/index.tsx` and re-exported for use by edit and columns files.

```typescript
export type ClubOption = {
    id: string;
    name: string;
};

export type CoachMembership = {
    id: string;
    club_id: string;
    started_at: string;
    ended_at: string | null;
    club?: ClubOption | null;
};

export type Coach = {
    id: string;
    user_id: string | null;
    full_name: string;
    email: string | null;
    phone_number: string | null;
    active_memberships?: CoachMembership[];
    created_at: string;
    updated_at: string;
};
```

---

## Pages

### `pages/coaches/index.tsx` — List

**Inertia Props**:
```typescript
type CoachesProps = {
    coaches: {
        data: Coach[];
        links: PaginationLink[];
    };
    filters: PageFilters;
};
```

**Layout**: `AppLayout`

**Components**:
- `Heading` — page title and description
- `DataTableControls` — keyword search, sort field, sort order
- `DataTable` — no `onRowClick`; navigation is via the Actions dropdown
- `DataTablePagination` — page links + per-page selector
- `Dialog` (shadcn) — delete confirmation modal

**Columns**: See `partials/columns.tsx` section below.

**URL state**: All filter changes call `router.get('/manage/coaches', params, { only: ['coaches'], preserveState: true, preserveScroll: true, replace: true })`. Keyword is debounced 500 ms. Changing search or sort resets `pageNumber` to 1.

**Sort options**: `full_name` (Name), `updated_at` (Last Updated). Default: `full_name asc`.

**Default per-page**: 10.

**Delete flow**: Clicking "Delete" in the row dropdown sets `coachToDelete` state, opening the confirmation dialog. Confirming calls `router.delete('/manage/coaches/{id}')`.

---

### `pages/coaches/create.tsx` — Create Form

**Inertia Props**: None — no controller-provided data. The component takes no props.

**Layout**: `AppLayout`

**Breadcrumbs**: Coaches → New Coach

**Form fields**:

| Field | Input type | Notes |
|---|---|---|
| `full_name` | Text input | Required |
| `email` | Email input | Optional |
| `phone_number` | Text input | Optional |

The page heading description reads: "Create a new coach. Assign them to clubs from the club page." — reinforcing that club assignment is a separate workflow.

**Submit**: `POST /manage/coaches`. Cancel links back to `/manage/coaches`.

---

### `pages/coaches/edit.tsx` — Edit Form

**Inertia Props**:
```typescript
interface Props {
    coach: Coach;
}
```

**Layout**: `AppLayout`

**Breadcrumbs**: Coaches → `{coach.full_name}`

**Pre-population**: `useForm` is initialised from `coach` props:
- `full_name`: `coach.full_name`
- `email`: `coach.email ?? ''`
- `phone_number`: `coach.phone_number ?? ''`

**Form fields**: Same three fields as create — `full_name`, `email`, `phone_number`.

**Active Clubs section** (read-only): Below the form fields, a labelled section shows the coach's active club memberships from `coach.active_memberships`. Each active membership renders as a `<Badge variant="secondary">` wrapped in an `<Link href="/manage/clubs/{club_id}/coaches">`. If `active_memberships` is empty, shows a muted message: "Not assigned to any club. Assign from the club's page."

**Submit**: `PUT /manage/coaches/{id}`. Cancel links back to `/manage/coaches`.

---

### `pages/coaches/partials/columns.tsx` — Table Columns

Factory function signature:
```typescript
type CoachColumnsProps = { onDelete: (coach: Coach) => void };
export const getCoachColumns = ({ onDelete }: CoachColumnsProps): ColumnDef<Coach>[]
```

| Column | Header | Accessor / Value | Cell renderer |
|---|---|---|---|
| full_name | Name | `accessorKey: 'full_name'` | Plain text (no custom renderer) |
| clubs | Active Clubs | computed from `active_memberships` | Comma-separated club names (`m.club?.name ?? m.club_id`). Muted `—` if no active memberships. |
| email | Email | `accessorKey: 'email'` | Email string or muted `—` if null |
| phone_number | Phone | `accessorKey: 'phone_number'` | Phone string or muted `—` if null |
| updated_at | Last Updated | `accessorKey: 'updated_at'` | `formatUTCDate(updated_at)` |
| actions | — | id: 'actions' | `DropdownMenu` with "Edit" (navigates to `/manage/coaches/{id}/edit`) and "Delete" (calls `onDelete`) |

---

## Navigation Map

| Action | URL | HTTP Method |
|---|---|---|
| List coaches | `/manage/coaches` | GET |
| Create form | `/manage/coaches/create` | GET |
| Store new coach | `/manage/coaches` | POST |
| Edit form | `/manage/coaches/{id}/edit` | GET |
| Update coach | `/manage/coaches/{id}` | PUT |
| Delete coach | `/manage/coaches/{id}` | DELETE |
| Club coaches tab (for assignment) | `/manage/clubs/{id}/coaches` | GET |
