# Players Pages — Wiki

## Overview

The Players module manages individual tournament competitors. It has three pages (list, create, edit) plus a columns partial. Players carry demographic data (name, gender, date of birth, weight, nationality), optional contact info (email, phone), a profile photo, and an optional current belt rank. There is no separate show/detail page — the edit page is the primary detail view.

---

## Types

Defined in `resources/js/pages/players/index.tsx` and re-exported for use by create, edit, and columns files.

```typescript
export enum PlayerGender {
    Male = 0,
    Female = 1,
}

export type BeltRankOption = {
    id: string;
    code: string;
    name: string;
    color: string | null;
};

export type CountryOption = {
    code: string;
    name: string;
};

export type Player = {
    id: string;
    name: string;
    gender: PlayerGender;
    dob: string;           // ISO date string, e.g. "1995-03-21"
    weight: number;        // kg
    nationality_code: string | null;
    current_belt_rank_id: string | null;
    country?: CountryOption;
    current_belt_rank?: BeltRankOption;
    email: string | null;
    profile_photo_url: string | null;
    phone_number: string | null;
    created_at: string;
    updated_at: string;
};
```

---

## Pages

### `pages/players/index.tsx` — List

**Inertia Props**:
```typescript
type PlayersProps = {
    players: {
        data: Player[];
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

**URL state**: All filter changes call `router.get('/manage/players', params, { only: ['players'], preserveState: true, preserveScroll: true, replace: true })`. Keyword is debounced 500 ms before updating `debouncedSearch`. Changing search or sort resets `pageNumber` to 1.

**Sort options**: `name` (Name), `updated_at` (Last Updated). Default: `name asc`.

**Default per-page**: 10.

**Delete flow**: Clicking "Delete" in the row dropdown sets `playerToDelete` state, opening the confirmation dialog. Confirming calls `router.delete('/manage/players/{id}')`.

---

### `pages/players/create.tsx` — Create Form

**Inertia Props**:
```typescript
interface Props {
    countries: CountryOption[];
    beltRanks: BeltRankOption[];
}
```

**Layout**: `AppLayout`

**Breadcrumbs**: Players → New Player

**Form fields**:

| Field | Input type | Notes |
|---|---|---|
| `profile_photo` | `FileUploader` (image/*, max 5 MB) | Optional |
| `name` | Text input | Required |
| `gender` | `RadioGroup` — "Male" / "Female" | Default: `PlayerGender.Male` (0). Values sent as integers. |
| `dob` | shadcn `Calendar` inside `Popover` | Required. Displayed as formatted date (date-fns `format(date, 'PPP')`). Sent as `yyyy-MM-dd`. Disabled for future dates and dates before 1900-01-01. |
| `weight` | Number input (step 0.01) | Required — weight in kg |
| `nationality_code` | Select from `countries` prop | Optional |
| `current_belt_rank_id` | Select from `beltRanks` prop | Optional. Each option shows `{code} — {name}`. |
| `email` | Email input | Optional |
| `phone_number` | Text input | Optional |

**DOB picker**: Local `date` state (`useState<Date>`) drives the Calendar. On date selection, `setData('dob', format(d, 'yyyy-MM-dd'))` updates the form value. The trigger button shows "Pick a date" when unset (muted) or the formatted date.

**Submit**: `POST /manage/players` (no `preserveScroll`). Cancel links back to `/manage/players`.

---

### `pages/players/edit.tsx` — Edit Form

**Inertia Props**:
```typescript
interface Props {
    player: Player;
    countries: CountryOption[];
    beltRanks: BeltRankOption[];
}
```

**Layout**: `AppLayout`

**Breadcrumbs**: Players → `{player.name}`

**Pre-population**: `useForm` is initialised from `player` props:
- `name`: `player.name`
- `gender`: `player.gender`
- `dob`: `player.dob ? format(new Date(player.dob), 'yyyy-MM-dd') : ''`
- `weight`: `player.weight.toString()`
- `nationality_code`: `player.nationality_code ?? ''`
- `current_belt_rank_id`: `player.current_belt_rank_id ?? ''`
- `email`: `player.email ?? ''`
- `phone_number`: `player.phone_number ?? ''`
- `profile_photo`: `null` (new file must be selected to change the photo)

Local `date` state is initialised as `player.dob ? new Date(player.dob) : undefined`.

`FileUploader` receives `initialPreview={player.profile_photo_url}` to display the current photo.

**Form fields**: Identical to `create.tsx`.

**Submit**: `POST /manage/players/{id}` (POST used to support multipart file upload for profile photo). Cancel links back to `/manage/players`.

---

### `pages/players/partials/columns.tsx` — Table Columns

Factory function signature:
```typescript
type PlayerColumnsProps = { onDelete: (player: Player) => void };
export const getPlayerColumns = ({ onDelete }: PlayerColumnsProps): ColumnDef<Player>[]
```

A local `calculateAge(dob: string): number` helper computes age from DOB by subtracting year-of-birth from today, adjusting for whether the birthday has occurred this year.

| Column | Header | Accessor / Value | Cell renderer |
|---|---|---|---|
| name | Name | `accessorKey: 'name'` | `Avatar` (profile photo or initials fallback, h-8 w-8) + player name in `<span>` |
| gender | Gender | `accessorKey: 'gender'` | `'Male'` if `PlayerGender.Male`, else `'Female'` |
| dob | Age | `accessorKey: 'dob'` | `calculateAge(dob)` — displays computed integer age, not raw DOB |
| weight | Weight (Kg) | `accessorKey: 'weight'` | Plain number (no custom renderer) |
| phone_number | Phone Number | `accessorKey: 'phone_number'` | `<a href="tel:...">` if present, else `'-'` |
| updated_at | Last Updated | `accessorKey: 'updated_at'` | `formatUTCDate(updated_at)` |
| actions | — | id: 'actions' | `DropdownMenu` with "Edit" (navigates to `/manage/players/{id}/edit`) and "Delete" (calls `onDelete`) |

---

## Navigation Map

| Action | URL | HTTP Method |
|---|---|---|
| List players | `/manage/players` | GET |
| Create form | `/manage/players/create` | GET |
| Store new player | `/manage/players` | POST |
| Edit form | `/manage/players/{id}/edit` | GET |
| Update player | `/manage/players/{id}` | POST |
| Delete player | `/manage/players/{id}` | DELETE |
