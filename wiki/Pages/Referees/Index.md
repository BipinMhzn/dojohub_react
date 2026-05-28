# Referees Pages — Wiki

## Overview

The Referees module manages tournament match officials. It has three pages (list, create, edit) plus a columns partial. Each referee has a full name, optional nationality, and an optional certification level (Club, National, Continental, International). There is no profile photo or club assignment — referees are standalone records.

---

## Types

Defined in `resources/js/pages/referees/index.tsx` and re-exported for use by create, edit, and columns files.

```typescript
export enum RefereeCertificationLevel {
    Club = 0,
    National = 1,
    Continental = 2,
    International = 3,
}

export const certificationLevelLabels: Record<RefereeCertificationLevel, string> = {
    [RefereeCertificationLevel.Club]: 'Club',
    [RefereeCertificationLevel.National]: 'National',
    [RefereeCertificationLevel.Continental]: 'Continental',
    [RefereeCertificationLevel.International]: 'International',
};

export type CountryOption = {
    code: string;
    name: string;
};

export type CertificationLevelOption = {
    name: string;
    value: number;
};

export type Referee = {
    id: string;
    user_id: string | null;
    full_name: string;
    nationality_code: string | null;
    certification_level: RefereeCertificationLevel | null;
    country?: CountryOption;
    created_at: string;
    updated_at: string;
};
```

**Certification level enum values**:
| Value | Label |
|---|---|
| 0 | Club |
| 1 | National |
| 2 | Continental |
| 3 | International |

---

## Pages

### `pages/referees/index.tsx` — List

**Inertia Props**:
```typescript
type RefereesProps = {
    referees: {
        data: Referee[];
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

**URL state**: All filter changes call `router.get('/manage/referees', params, { only: ['referees'], preserveState: true, preserveScroll: true, replace: true })`. Keyword is debounced 500 ms. Changing search or sort resets `pageNumber` to 1.

**Sort options**: `full_name` (Name), `updated_at` (Last Updated). Default: `full_name asc`.

**Default per-page**: 10.

**Delete flow**: Clicking "Delete" in the row dropdown sets `refereeToDelete` state, opening the confirmation dialog. Confirming calls `router.delete('/manage/referees/{id}')`.

---

### `pages/referees/create.tsx` — Create Form

**Inertia Props**:
```typescript
interface Props {
    countries: CountryOption[];
}
```

**Layout**: `AppLayout`

**Breadcrumbs**: Referees → New Referee

**Form fields**:

| Field | Input type | Notes |
|---|---|---|
| `full_name` | Text input | Required |
| `nationality_code` | Select from `countries` prop | Optional |
| `certification_level` | Select built from `RefereeCertificationLevel` enum | Optional. Values are iterated via `Object.entries(RefereeCertificationLevel).filter(([, value]) => typeof value === 'number')`. Sent as string of the integer value (e.g. `"0"`, `"1"`). |

**Certification level select rendering**: The enum is iterated at runtime, filtering numeric values only (since TypeScript numeric enums produce both `{ Club: 0, 0: 'Club', ... }`). Labels come from `certificationLevelLabels`.

**Submit**: `POST /manage/referees`. Cancel links back to `/manage/referees`.

---

### `pages/referees/edit.tsx` — Edit Form

**Inertia Props**:
```typescript
interface Props {
    referee: Referee;
    countries: CountryOption[];
}
```

**Layout**: `AppLayout`

**Breadcrumbs**: Referees → `{referee.full_name}`

**Pre-population**: `useForm` is initialised from `referee` props:
- `full_name`: `referee.full_name`
- `nationality_code`: `referee.nationality_code ?? ''`
- `certification_level`: `referee.certification_level !== null && referee.certification_level !== undefined ? String(referee.certification_level) : ''`

The null-check on `certification_level` is explicit — it guards against `0` (Club) being falsy and incorrectly treated as "no value".

**Form fields**: Identical to `create.tsx` — `full_name`, `nationality_code`, `certification_level`.

**Submit**: `PUT /manage/referees/{id}`. Cancel links back to `/manage/referees`.

---

### `pages/referees/partials/columns.tsx` — Table Columns

Factory function signature:
```typescript
type RefereeColumnsProps = { onDelete: (referee: Referee) => void };
export const getRefereeColumns = ({ onDelete }: RefereeColumnsProps): ColumnDef<Referee>[]
```

| Column | Header | Accessor / Value | Cell renderer |
|---|---|---|---|
| full_name | Name | `accessorKey: 'full_name'` | Plain text (no custom renderer) |
| country | Nationality | `accessorKey: 'country'` | `row.original.country?.name ?? '-'` |
| certification_level | Certification Level | `accessorKey: 'certification_level'` | `certificationLevelLabels[level]` if not null/undefined, else `'-'` |
| updated_at | Last Updated | `accessorKey: 'updated_at'` | `formatUTCDate(updated_at)` |
| actions | — | id: 'actions' | `DropdownMenu` with "Edit" (navigates to `/manage/referees/{id}/edit`) and "Delete" (calls `onDelete`) |

---

## Navigation Map

| Action | URL | HTTP Method |
|---|---|---|
| List referees | `/manage/referees` | GET |
| Create form | `/manage/referees/create` | GET |
| Store new referee | `/manage/referees` | POST |
| Edit form | `/manage/referees/{id}/edit` | GET |
| Update referee | `/manage/referees/{id}` | PUT |
| Delete referee | `/manage/referees/{id}` | DELETE |
