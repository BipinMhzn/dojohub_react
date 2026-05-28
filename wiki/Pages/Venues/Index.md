# Venues Pages — Wiki

## Overview

The Venues module manages physical tournament venue locations and their tatami mats. It covers four pages (list, create, show/edit-overview, mats) plus a dedicated mats sub-page, all using `AppLayout`. Show and Mats pages additionally wrap content in `VenueLayout` (a tabbed sidebar layout with "Overview" and "Mats" tabs).

---

## Types

Defined in `resources/js/pages/venues/index.tsx` and re-exported for use by create, edit, show, and mats pages.

```typescript
export type VenueAddress = {
    id: string;
    address_line_1: string;
    address_line_2: string | null;
    city: string;
    state: string | null;
    postal_code: string | null;
    country_code: string;
    country?: { code: string; name: string };
};

export type VenueMat = {
    id: string;
    venue_id: string;
    mat_number: number;
    display_name: string | null;
};

export type Venue = {
    id: string;
    name: string;
    mats_count: number;
    address: VenueAddress;
    mats?: VenueMat[];
    created_at: string;
    updated_at: string;
};

export type Country = {
    code: string;
    name: string;
};
```

Note: `VenueAddress` also carries `latitude` and `longitude` fields on the server (referenced as `venue.address?.latitude` in `edit.tsx`) but these are not typed in the TypeScript interface.

---

## Pages

### `pages/venues/index.tsx` — List

**Inertia Props**:
```typescript
type VenuesProps = {
    venues: {
        data: Venue[];
        links: PaginationLink[];
    };
    filters: {
        keyword?: string;
        sort_by?: string;
        sort_order?: string;
        page?: number;
        per_page?: number;
    };
};
```

**Layout**: `AppLayout`

**Components**:
- `Heading` — page title and description
- `DataTableControls` — keyword search, sort field, sort order
- `DataTable` — row-click navigates to `/manage/venues/{id}`
- `DataTablePagination` — page links + per-page selector
- `Dialog` (shadcn) — delete confirmation modal

**Columns**: See `partials/columns.tsx` section below.

**URL state**: All filter changes call `router.get('/manage/venues', params, { only: ['venues'], preserveState: true, preserveScroll: true, replace: true })`. A `hasMounted` ref prevents a spurious request on first render. Keyword input is debounced 500 ms before updating `debouncedSearch` (which triggers the router call). Changing search or sort resets `pageNumber` to 1.

**Sort options**: `name` (Name), `updated_at` (Last Updated). Default: `name asc`.

**Default per-page**: 10.

**Delete flow**: Clicking "Delete" in the row dropdown sets `venueToDelete` state, opening the confirmation dialog. Confirming calls `router.delete('/manage/venues/{id}')`.

---

### `pages/venues/create.tsx` — Create Form

**Inertia Props**:
```typescript
interface Props {
    countries: Country[];
}
```

**Layout**: `AppLayout`

**Breadcrumbs**: Venues → New Venue

**Form fields**:

| Field | Input type | Notes |
|---|---|---|
| `name` | Text input | Required |
| `country_code` | Select (from `countries` prop) | Required |
| `state` | Text input | Optional (placeholder shown) |
| `city` | Text input | Required |
| `address_line_1` | Text input | Required |
| `address_line_2` | Text input | Optional |
| `postal_code` | Text input | Optional |
| `latitude` | Text input (in `useForm` but not rendered in the JSX) | Hidden — included in form data but no UI field |
| `longitude` | Text input (in `useForm` but not rendered in the JSX) | Hidden — included in form data but no UI field |

**Submit**: `POST /manage/venues` with `preserveScroll: true`. On success, `reset()` clears the form. Cancel links back to `/manage/venues`.

---

### `pages/venues/edit.tsx` — Edit Form

**Inertia Props**:
```typescript
interface Props {
    venue: Venue;
    countries: Country[];
}
```

**Layout**: `AppLayout`

**Breadcrumbs**: Venues → `{venue.name}`

**Pre-population**: `useForm` initialises all fields from `venue` and `venue.address`. `latitude` and `longitude` come from `venue.address?.latitude?.toString()` / `venue.address?.longitude?.toString()` (falling back to `''`) — not rendered in the UI.

**Form fields**: Identical to Create (name, country_code, state, city, address_line_1, address_line_2, postal_code, latitude, longitude).

**Submit**: `PUT /manage/venues/{id}` with `preserveScroll: true`.

**Mats section**: The edit page also embeds a full Tatami Mats manager below the address form (same functionality as `mats/index.tsx`):
- Displays existing mats in a table (Mat #, Display Name, Remove button).
- Separate `useForm({ mat_number, display_name })` drives an inline "Add Mat" form that `POST`s to `/manage/venues/{id}/mats`.
- Remove calls `router.delete('/manage/venues/{id}/mats/{mat.id}')`.

---

### `pages/venues/show.tsx` — Detail / Overview Tab

**Inertia Props**:
```typescript
interface Props {
    venue: Venue;
    countries: Country[];
}
```

**Layout**: `AppLayout` wrapping `VenueLayout` with `activeKey="overview"`.

**Breadcrumbs**: Venues → `{venue.name}`

**Behaviour**: Renders an inline edit form for the venue inside `VenueLayout`. This is functionally the edit form served at the "Overview" tab of the venue detail view.

**Form fields**: Identical to `edit.tsx` except address fields are grouped inside a `<fieldset>` labelled "Address", and `latitude`/`longitude` are not in the `useForm` initialisation.

**Submit**: `PUT /manage/venues/{id}` with `preserveScroll: true`. Button label: "Save Changes".

---

### `pages/venues/mats/index.tsx` — Mats Tab

**Inertia Props**:
```typescript
interface Props {
    venue: Venue;
}
```

**Layout**: `AppLayout` wrapping `VenueLayout` with `activeKey="mats"`.

**Breadcrumbs**: Venues → `{venue.name}` → Mats

**Behaviour**:
- Displays current `venue.mats` in a table (Mat Number, Display Name, Remove button). Shows mat count in top-right.
- Inline "Add Mat" form below the table: `mat_number` (number, 1–99) and `display_name` (text, optional placeholder "e.g. Tatami A").
- Add: `POST /manage/venues/{id}/mats` via `matForm.post(...)`, resets form on success.
- Remove: `router.delete('/manage/venues/{id}/mats/{mat.id}', { preserveScroll: true })`.

---

### `pages/venues/partials/columns.tsx` — Table Columns

Factory function signature:
```typescript
type VenueColumnsProps = { onDelete: (venue: Venue) => void };
export const getVenueColumns = ({ onDelete }: VenueColumnsProps): ColumnDef<Venue>[]
```

| Column | Header | Accessor / Value | Cell renderer |
|---|---|---|---|
| name | Name | `accessorKey: 'name'` | `<span className="font-medium">{name}</span>` |
| address | Address | computed | `address?.address_line_1 ?? '—'` |
| city | City | computed | `address?.city ?? '—'` |
| country | Country | computed | `address?.country?.name ?? address?.country_code ?? '—'` |
| mats | Mats | computed | `mats_count ?? 0` |
| updated_at | Last Updated | `accessorKey: 'updated_at'` | `formatUTCDate(updated_at)` |
| actions | — | id: 'actions' | `DropdownMenu` with "Edit" (navigates to `/manage/venues/{id}/edit`) and "Delete" (calls `onDelete`) items. Click is stopped from propagating to the row. |

---

## Navigation Map

| Action | URL | HTTP Method |
|---|---|---|
| List venues | `/manage/venues` | GET |
| Create form | `/manage/venues/create` | GET |
| Store new venue | `/manage/venues` | POST |
| Show / Overview tab | `/manage/venues/{id}` | GET |
| Edit form | `/manage/venues/{id}/edit` | GET |
| Update venue | `/manage/venues/{id}` | PUT |
| Delete venue | `/manage/venues/{id}` | DELETE |
| Mats tab | `/manage/venues/{id}/mats` | GET |
| Add mat | `/manage/venues/{id}/mats` | POST |
| Delete mat | `/manage/venues/{id}/mats/{matId}` | DELETE |
