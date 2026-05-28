# Clubs Pages — Wiki

## Overview

The Clubs module manages martial arts clubs, their member players, and their coaches. It has six pages:
- `index.tsx` — paginated list of all clubs
- `create.tsx` — create a new club (with logo upload)
- `show.tsx` — view and edit club details (Overview tab, via `ClubLayout`)
- `players/index.tsx` — manage player memberships for a specific club (Players tab)
- `coaches/index.tsx` — manage coach memberships for a specific club (Coaches tab)

There is no separate `edit.tsx`; editing is done from `show.tsx` inside `ClubLayout`. The `show.tsx` page uses `POST` (not `PUT`) to handle multipart file uploads for the logo.

`ClubLayout` is a tabbed layout with keys: `"overview"`, `"players"`, `"coaches"`.

---

## Types

### From `resources/js/pages/clubs/index.tsx`

```typescript
export type ClubAddress = {
    id: string;
    address_line_1: string;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country_code: string;
    country?: { code: string; name: string };
};

export type Country = {
    code: string;
    name: string;
};

export type Club = {
    id: string;
    name: string;
    abbreviation: string;
    logo_url: string | null;
    address: ClubAddress | null;
    phone_number: string | null;
    created_at: string;
    updated_at: string;
};
```

### From `resources/js/pages/clubs/players/index.tsx`

```typescript
export type BeltRankModel = {
    id: string;
    code: string;
    name: string;
    color: string | null;
    sort_order: number;
};

export type ClubMembership = {
    id: string;
    club_id: string;
    player_id: string;
    player: {
        id: string;
        name: string;
        profile_photo_url: string | null;
    };
    started_at: string;
    ended_at: string | null;
    belt_rank_id: string | null;
    belt_rank: BeltRankModel | null;
    grade_certificate: string | null;
    created_at: string;
    updated_at: string;
};
```

### From `resources/js/pages/clubs/coaches/index.tsx`

```typescript
export type CoachOption = {
    id: string;
    full_name: string;
};

export type ClubCoachMembership = {
    id: string;
    club_id: string;
    coach_id: string;
    coach: CoachOption;
    started_at: string;
    ended_at: string | null;
    created_at: string;
    updated_at: string;
};
```

---

## Pages

### `pages/clubs/index.tsx` — List

**Inertia Props**:
```typescript
type ClubsProps = {
    clubs: {
        data: Club[];
        links: PaginationLink[];
    };
    filters: PageFilters;
};
```

**Layout**: `AppLayout`

**Components**:
- `Heading` — page title and description
- `DataTableControls` — keyword search, sort field, sort order
- `DataTable` — row-click navigates to `/manage/clubs/{id}`
- `DataTablePagination` — page links + per-page selector
- `Dialog` (shadcn) — delete confirmation modal

**Columns**: See `partials/columns.tsx` section below.

**URL state**: All filter changes call `router.get('/manage/clubs', params, { only: ['clubs'], preserveState: true, preserveScroll: true, replace: true })`. Keyword is debounced 500 ms. Changing search or sort resets `pageNumber` to 1.

**Sort options**: `name` (Name), `updated_at` (Last Updated). Default: `name asc`.

**Default per-page**: 10.

**Delete flow**: Clicking "Delete" in the row dropdown sets `clubToDelete` state, opening the confirmation dialog. Confirming calls `router.delete('/manage/clubs/{id}')`.

---

### `pages/clubs/create.tsx` — Create Form

**Inertia Props**:
```typescript
interface Props {
    countries: Country[];
}
```

**Layout**: `AppLayout`

**Breadcrumbs**: Clubs → New Club

**Form fields**:

| Field | Input type | Notes |
|---|---|---|
| `logo` | `FileUploader` (image/*, max 5 MB) | Optional |
| `name` | Text input | Required |
| `abbreviation` | Text input | Required |
| `phone_number` | Text input | Optional |
| `country_code` | Select (from `countries` prop) | Optional (inside Address fieldset) |
| `state` | Text input | Optional |
| `city` | Text input | Optional |
| `address_line_1` | Text input | Optional |
| `address_line_2` | Text input | Optional |
| `postal_code` | Text input | Optional |

Address fields are grouped inside an optional `<fieldset>` labelled "Address (Optional)".

**Submit**: `POST /manage/clubs` with `preserveScroll: true`. On success, `reset()` clears the form. Cancel links back to `/manage/clubs`.

**File upload note**: `logo` is `null as File | null` in `useForm`. The `FileUploader` component calls `setData('logo', file)` on selection. Inertia serialises this as multipart.

---

### `pages/clubs/show.tsx` — Detail / Overview Tab

**Inertia Props**:
```typescript
interface Props {
    club: Club;
    countries: Country[];
}
```

**Layout**: `AppLayout` wrapping `ClubLayout` with `activeKey="overview"`.

**Breadcrumbs**: Clubs → `{club.name}`

**Behaviour**: Renders the club edit form inside `ClubLayout`. The `FileUploader` shows `initialPreview={club.logo_url}` so the existing logo is visible.

**Pre-population**: All fields initialised from `club` props. `logo` always starts as `null` (a new file must be selected to change the logo).

**Form fields**: Identical to `create.tsx` — logo, name, abbreviation, phone_number, plus the address fieldset.

**Submit**: `POST /manage/clubs/{id}` with `preserveScroll: true` (POST used instead of PUT to support file upload). Button label: "Save Changes".

---

### `pages/clubs/players/index.tsx` — Players Tab

**Inertia Props**:
```typescript
type ClubPlayersProps = {
    club: Club;
    memberships: {
        data: ClubMembership[];
        links: PaginationLink[];
    };
    availablePlayers: Pick<Player, 'id' | 'name'>[];
    beltRanks: BeltRankModel[];
    filters: PageFilters;
};
```

**Layout**: `AppLayout` wrapping `ClubLayout` with `activeKey="players"`.

**Breadcrumbs**: Clubs → `{club.name}` → Players

**Components**: `DataTableControls` (with status filter), `DataTable`, `DataTablePagination`, two `Dialog` modals.

**URL state**: `router.get('/manage/clubs/{id}/players', params, { only: ['memberships', 'filters'], ... })`. Filters include `keyword`, `status`, `sort_by`, `sort_order`, `page`, `per_page`.

**Sort options**: `started_at` (Joined Date), `ended_at` (Ended Date). Default: `started_at desc`.

**Status filter**: `active` (default), `ended`, `all`. Toggling an already-selected status cycles to `all`; toggling `all` returns to `active`.

**Default per-page**: 15.

**Assign Player dialog** (`POST /manage/clubs/{id}/players`):

| Field | Input type | Notes |
|---|---|---|
| `player_id` | Select from `availablePlayers` | Required; shows empty-state message if no players available |
| `started_at` | Calendar in Popover (date-fns `format(date, 'yyyy-MM-dd')`) | Required; defaults to today; cannot be future date |
| `belt_rank_id` | Select from `beltRanks` | Optional |
| `grade_certificate` | Text input (placeholder "e.g. 1st dan") | Optional |

Dialog is modal (`onInteractOutside` prevented). Resets form on success.

**End Membership dialog** (`PUT /manage/clubs/{id}/players/{membershipId}`):

| Field | Input type | Notes |
|---|---|---|
| `ended_at` | Calendar in Popover | Required; must be between `membership.started_at` and today (both inclusive) |

Triggered by "End Membership" button in active membership rows. Resets on success.

---

### `pages/clubs/coaches/index.tsx` — Coaches Tab

**Inertia Props**:
```typescript
type ClubCoachesProps = {
    club: Club;
    memberships: {
        data: ClubCoachMembership[];
        links: PaginationLink[];
    };
    availableCoaches: CoachOption[];
    filters: PageFilters;
};
```

**Layout**: `AppLayout` wrapping `ClubLayout` with `activeKey="coaches"`.

**Breadcrumbs**: Clubs → `{club.name}` → Coaches

**Behaviour**: Mirrors the Players tab structure. Uses the same status filter (`active`/`ended`/`all`) and the same assign + end-membership dialog pattern.

**URL state**: `router.get('/manage/clubs/{id}/coaches', params, { only: ['memberships', 'filters'], ... })`.

**Sort options**: `started_at` (Joined Date), `ended_at` (Ended Date). Default: `started_at desc`.

**Default per-page**: 15.

**Assign Coach dialog** (`POST /manage/clubs/{id}/coaches`):

| Field | Input type | Notes |
|---|---|---|
| `coach_id` | Select from `availableCoaches` | Required; shows empty-state if no coaches available |
| `started_at` | Calendar in Popover | Required; defaults to today; cannot be future date |

No belt rank or grade certificate — coaches have fewer assignment fields than players.

**End Membership dialog** (`PUT /manage/clubs/{id}/coaches/{membershipId}`):

| Field | Input type | Notes |
|---|---|---|
| `ended_at` | Calendar in Popover | Required; must be between `membership.started_at` and today |

---

### `pages/clubs/partials/columns.tsx` — Club Table Columns

Factory function signature:
```typescript
type ClubColumnsProps = { onDelete: (player: Club) => void };
export const getClubColumns = ({ onDelete }: ClubColumnsProps): ColumnDef<Club>[]
```

| Column | Header | Accessor / Value | Cell renderer |
|---|---|---|---|
| name | Name | `accessorKey: 'name'` | `Avatar` (logo or initials fallback) + club name in `<span>` |
| abbreviation | Abbreviation | `accessorKey: 'abbreviation'` | Plain text |
| address | Address | computed | `address?.address_line_1 ?? '—'` |
| city | City | computed | `address?.city ?? '—'` |
| country | Country | computed | `address?.country?.name ?? address?.country_code ?? '—'` |
| phone_number | Phone Number | `accessorKey: 'phone_number'` | `<a href="tel:...">` if present, else `'—'` (click stops propagation) |
| updated_at | Last Updated | `accessorKey: 'updated_at'` | `formatUTCDate(updated_at)` |
| actions | — | id: 'actions' | `DropdownMenu` with "Edit" (navigates to `/manage/clubs/{id}`) and "Delete" (calls `onDelete`). Click stops propagation. |

---

### `pages/clubs/players/partials/columns.tsx` — Club Players Table Columns

Factory function signature:
```typescript
type ClubPlayerColumnsProps = { onEndMembership: (membership: ClubMembership) => void };
export const getClubPlayerColumns = ({ onEndMembership }: ClubPlayerColumnsProps): ColumnDef<ClubMembership>[]
```

| Column | Header | Value | Cell renderer |
|---|---|---|---|
| player | Player | `player.name` / `player.profile_photo_url` | `Avatar` (photo or initials) + player name |
| belt_rank | Belt Rank | `belt_rank.name` | Text, or muted `—` if null |
| grade_certificate | Grade Certificate | `grade_certificate` | Text, or muted `—` if null |
| started_at | Joined | `accessorKey: 'started_at'` | `formatUTCDate(started_at)` |
| status | Status | `ended_at` | `Badge` — "Active" (default variant) if no end date, "Ended {date}" (secondary variant) if ended |
| actions | — | id: 'actions' | "End Membership" `Button` (outline, sm) — only rendered when `ended_at` is null |

---

### `pages/clubs/coaches/partials/columns.tsx` — Club Coaches Table Columns

Factory function signature:
```typescript
type ClubCoachColumnsProps = { onEndMembership: (membership: ClubCoachMembership) => void };
export const getClubCoachColumns = ({ onEndMembership }: ClubCoachColumnsProps): ColumnDef<ClubCoachMembership>[]
```

| Column | Header | Value | Cell renderer |
|---|---|---|---|
| coach | Coach | `coach.full_name` | Plain text (falls back to `coach_id` if `full_name` absent) |
| started_at | Joined | `accessorKey: 'started_at'` | `formatUTCDate(started_at)` |
| status | Status | `ended_at` | `Badge` — "Active" or "Ended {date}" (same pattern as player columns) |
| actions | — | id: 'actions' | "End Membership" `Button` — only rendered when `ended_at` is null |

---

## Navigation Map

| Action | URL | HTTP Method |
|---|---|---|
| List clubs | `/manage/clubs` | GET |
| Create form | `/manage/clubs/create` | GET |
| Store new club | `/manage/clubs` | POST |
| Show / Overview tab | `/manage/clubs/{id}` | GET |
| Update club (with logo) | `/manage/clubs/{id}` | POST |
| Delete club | `/manage/clubs/{id}` | DELETE |
| Players tab | `/manage/clubs/{id}/players` | GET |
| Assign player to club | `/manage/clubs/{id}/players` | POST |
| End player membership | `/manage/clubs/{id}/players/{membershipId}` | PUT |
| Coaches tab | `/manage/clubs/{id}/coaches` | GET |
| Assign coach to club | `/manage/clubs/{id}/coaches` | POST |
| End coach membership | `/manage/clubs/{id}/coaches/{membershipId}` | PUT |
