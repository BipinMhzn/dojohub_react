# Tournaments Pages — Wiki

## Overview

The Tournaments module is the core of DojoHub. It covers tournament lifecycle management from creation through final results, spanning 11 pages organised across three layout contexts:

- **`AppLayout` only** — Tournament list (`index.tsx`) and tournament create (`create.tsx`). These pages do not use a sub-layout.
- **`AppLayout` + `TournamentLayout`** — Tournament overview/edit (`show.tsx`) and categories list (`categories/index.tsx`). `TournamentLayout` provides a tab navigation with "Overview" and "Categories" tabs.
- **`AppLayout` + `CategoryLayout`** — Category edit (`categories/edit.tsx`), registrations (`registrations/index.tsx`), matches (`matches/index.tsx`), and results (`results/index.tsx`). `CategoryLayout` provides a tab navigation with "Overview", "Registrations", "Matches", and "Results" tabs.
- **`AppLayout` only** — Category create (`categories/create.tsx`).

All tournament types and enums are defined in `resources/js/pages/tournaments/index.tsx` and re-exported.

---

## Types

### Enums

```typescript
export enum TournamentStatus {
    Scheduled = 0,
    InProgress = 1,
    Completed = 2,
    Cancelled = 3,
}

export enum TournamentLevel {
    Local = 1,
    Regional = 2,
    National = 3,
    International = 4,
}

export enum CategoryStatus {
    Pending = 0,
    Seeding = 1,
    Active = 2,
    Completed = 3,
}

export enum RegistrationStatus {
    Registered = 0,
    Confirmed = 1,
    Withdrawn = 2,
}

export enum Gender {
    Male = 0,
    Female = 1,
}

export enum AgeCategory {
    Preparation = 0,
    SuperMini = 1,
    Mini = 2,
    Pupils = 3,
    Juveniles = 4,
    Cadets = 5,
    Juniors = 6,
    Under23 = 7,
    Seniors = 8,
    Veterans = 9,
}

export enum WeightCategory {
    Female48 = 0,
    Female52 = 1,
    Female57 = 2,
    Male60 = 3,
    Female63 = 4,
    Male66 = 5,
    Female70 = 6,
    Male73 = 7,
    Female78 = 8,
    Male81 = 9,
    Male90 = 10,
    Male100 = 11,
    Male100Plus = 12,
    Female78Plus = 13,
}

export enum BracketType {
    SingleElimination = 0,
    DoubleElimination = 1,
    PoolFinal = 2,
    Repechage = 3,
}

export enum BracketStatus {
    Pending = 0,
    Active = 1,
    Completed = 2,
}

export enum BracketSection {
    Main = 0,
    Repechage = 1,
    Bronze = 2,
    Final = 3,
}

export enum MatchStatus {
    Pending = 0,
    Active = 1,
    Completed = 2,
}

export enum CompetitorPosition {
    White = 0,
    Blue = 1,
}

export enum CompetitorResult {
    Pending = 0,
    Win = 1,
    Loss = 2,
    WalkoverWin = 3,
    Disqualified = 4,
}
```

### Core Object Types

```typescript
export type TournamentRuleSet = {
    id: string;
    name: string;
};

export type Tournament = {
    id: string;
    name: string;
    description: string | null;
    logo_url: string | null;
    level: TournamentLevel;
    start_at: string;
    end_at: string | null;
    status: TournamentStatus;
    venue_id: string;
    rule_set_id: string | null;
    timezone: string | null;
    rule_set?: TournamentRuleSet | null;
    created_at: string;
    updated_at: string;
};

export type TournamentCategory = {
    id: string;
    tournament_id: string;
    name: string | null;
    gender: Gender;
    age_category: AgeCategory;
    min_age: number;
    max_age: number;
    weight_category: WeightCategory;
    min_weight: number;
    max_weight: number;
    status: CategoryStatus;
    registration_open_at: string | null;
    registration_close_at: string | null;
    start_at: string | null;
    end_at: string | null;
    weigh_in_start_at: string | null;
    weigh_in_end_at: string | null;
    registrations_count?: number;
    created_at: string;
    updated_at: string;
};

export type TournamentRegistration = {
    id: string;
    tournament_category_id: string;
    club_id: string;
    player_id: string;
    player_age: number;
    player_weight: number;
    status: RegistrationStatus;
    player?: {
        id: string;
        name: string;
        profile_photo_url: string | null;
    };
    club?: {
        id: string;
        name: string;
    };
};
```

### Bracket / Match Types

```typescript
export type MatchCompetitor = {
    registration_id: string;
    player_id: string;
    name: string;
    seed: number | null;
    score: number | null;
    penalties: number | null;
    result: CompetitorResult;
    profile_photo_url: string | null;
};

export type BracketMatch = {
    id: string;
    match_number: number;
    white: MatchCompetitor | null;
    blue: MatchCompetitor | null;
    winner_id: string | null;
    status: MatchStatus;
    scheduled_at: string | null;
    notes: string | null;
};

export type BracketRound = {
    round_number: number;
    round_name: string;
    matches: BracketMatch[];
};

export type BracketStructure = {
    id: string;
    type: BracketType;
    status: BracketStatus;
    sections: {
        Main?: BracketRound[];
        Repechage?: BracketRound[];
        Bronze?: BracketRound[];
        Final?: BracketRound[];
    };
};
```

### Result Type (local to `results/index.tsx`)

```typescript
interface TournamentResultRow {
    id: string;
    position: number;
    registration: {
        id: string;
        player_age: number;
        player_weight: number;
        player?: {
            id: string;
            name: string;
            profile_photo_url: string | null;
        };
        club?: {
            id: string;
            name: string;
        };
    };
}
```

### Bracket stub (local to `matches/index.tsx`)

```typescript
interface TournamentBracket {
    id: string;
    type: number;
    status: number;
}
```

---

## Partials

### `partials/status-badge.tsx` — `StatusBadge` Component

Renders a coloured `Badge` for a `TournamentStatus` numeric value.

```typescript
type StatusBadgeProps = { status: number };
export const StatusBadge = ({ status }: StatusBadgeProps) => { ... }
```

| Status value | Label | Badge colour |
|---|---|---|
| 0 (Scheduled) | Scheduled | Blue |
| 1 (InProgress) | In Progress | Green |
| 2 (Completed) | Completed | Grey |
| 3 (Cancelled) | Cancelled | Red |

### `partials/category-columns.tsx` — `getCategoryDisplayName`

```typescript
export function getCategoryDisplayName(category: TournamentCategory): string
```

Logic: if `category.name` is non-null, returns it directly. Otherwise builds `"{Gender} {AgeLabel} {WeightLabel}"` from lookup tables:

- Age labels: `Preparation`, `Super Mini`, `Mini`, `Pupils`, `Juveniles`, `Cadets`, `Juniors`, `Under 23`, `Seniors`, `Veterans`.
- Weight labels: `-48kg`, `-52kg`, `-57kg`, `-60kg`, `-63kg`, `-66kg`, `-70kg`, `-73kg`, `-78kg`, `-81kg`, `-90kg`, `-100kg`, `+100kg`, `+78kg`.

Used by `categories/edit.tsx`, `registrations/index.tsx`, `matches/index.tsx`, and `results/index.tsx` to derive the breadcrumb and page title from a category.

### `partials/columns.tsx` — `getTournamentColumns`

Factory function:
```typescript
type TournamentColumnsProps = { onDelete: (tournament: Tournament) => void };
export const getTournamentColumns = ({ onDelete }: TournamentColumnsProps): ColumnDef<Tournament>[]
```

| Column | Header | Cell renderer |
|---|---|---|
| name | Name | `Avatar` (initials fallback from `logo_url`) + bold name |
| level | Level | Switch on `TournamentLevel` enum → `Local / Regional / National / International` |
| duration | Duration | `formatUTCDate(start_at)` – `formatUTCDate(end_at)` or `TBD` |
| status | Status | `<StatusBadge status={...} />` |
| updated_at | Last Updated | `formatUTCDate(updated_at)` |
| actions | — | `DropdownMenu`: "Edit" navigates to `/manage/tournaments/{id}`, "Delete" calls `onDelete` |

### `partials/category-columns.tsx` — `getCategoryColumns`

Factory function:
```typescript
type CategoryColumnsProps = { tournamentId: string; onDelete: (category: TournamentCategory) => void };
export const getCategoryColumns = ({ tournamentId, onDelete }: CategoryColumnsProps): ColumnDef<TournamentCategory>[]
```

| Column | Header | Cell renderer |
|---|---|---|
| name | Category | `getCategoryDisplayName(row.original)` |
| age_range | Age | `{min_age}–{max_age} yrs` |
| weight_range | Weight | `{min_weight}–{max_weight} kg` |
| registrations_count | Registered | `registrations_count ?? 0` |
| status | Status | `Badge` with colour from `categoryStatusVariant` map |
| actions | — | `DropdownMenu`: "Edit" → `/manage/tournaments/{id}/categories/{id}/edit`, "Delete" calls `onDelete` |

Category status badge colours: Pending=Blue, Seeding=Yellow, Active=Green, Completed=Grey.

---

## Pages

### `pages/tournaments/index.tsx` — Tournament List

**Inertia Props**:
```typescript
type TournamentsProps = {
    tournaments: {
        data: Tournament[];
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
- `Heading` — "Tournaments" title + description
- `DataTableControls` — keyword search, sort by (`name` / `updated_at`), sort order
- `DataTable` — row click navigates to `/manage/tournaments/{id}`; columns from `getTournamentColumns`
- `DataTablePagination` — page links + per-page selector
- `Dialog` (shadcn) — delete confirmation modal

**Behaviour**:
- All filter/pagination state is controlled via React state: `search`, `sortBy`, `sortOrder`, `pageNumber`, `perPage`.
- Keyword input is debounced 500 ms before issuing a partial Inertia reload (`only: ['tournaments']`) with `preserveState: true`, `preserveScroll: true`, `replace: true`.
- A `hasMounted` ref prevents a spurious request on first mount.
- Changing `search` or any sort field resets `pageNumber` to 1.
- Default: `sortBy = 'name'`, `sortOrder = 'asc'`, `perPage = 10`.

**Delete flow**: Selecting "Delete" from a row dropdown sets `tournamentToDelete` state, opening a confirmation `Dialog`. Confirming calls `router.delete('/manage/tournaments/{id}')`.

**Navigation**: "New Tournament" button links to `/manage/tournaments/create`.

---

### `pages/tournaments/create.tsx` — Create Tournament

**Inertia Props**:
```typescript
interface Props {
    venues: Venue[];
    ruleSets: TournamentRuleSet[];
}
```

**Layout**: `AppLayout`

**Breadcrumbs**: Tournaments → Create Tournament

**Components**: `Heading`, `Input`, `Textarea`, `Select`, `Calendar` (inside `Popover`), `FileUploader`

**Form fields**:

| Field | Input type | Notes |
|---|---|---|
| `name` | Text | Required |
| `start_at` | Calendar date picker (Popover) | Required; converted to UTC string via `convertLocalDateToUTCString`; future dates only (`disabled: date < new Date()`) |
| `end_at` | Calendar date picker (Popover) | Optional |
| `level` | Select | Enum keys from `TournamentLevel` (Local, Regional, National, International) |
| `venue_id` | Select | From `venues` prop; shows "No venues found" if empty |
| `rule_set_id` | Select | From `ruleSets` prop; optional; shows "No rule sets found" if empty |
| `timezone` | Select | Populated from `Intl.supportedValuesOf('timeZone')`; normalises deprecated IANA aliases (e.g. `Asia/Katmandu` → `Asia/Kathmandu`); pre-selected to browser's detected timezone |
| `logo` | FileUploader | Optional; `accept="image/*"`, max 5 MB |
| `description` | Textarea | Optional; `min-h-32` |

**Timezone normalisation**: A `DEPRECATED_TZ_ALIASES` map corrects deprecated IANA names. The detected timezone is memoised.

**Submit**: `POST /manage/tournaments`. Cancel links to `/manage/tournaments`.

---

### `pages/tournaments/show.tsx` — Tournament Overview (Edit)

**Inertia Props**:
```typescript
interface Props {
    tournament: Tournament;
    venues: Venue[];
    ruleSets: TournamentRuleSet[];
}
```

**Layout**: `AppLayout` wrapping `TournamentLayout` with `activeKey="overview"`

**Breadcrumbs**: Tournaments → `{tournament.name}`

**Components**: `HeadingSmall`, `Input` (bare `<input>` for name), `Textarea`, `Select`, `Calendar` (Popover), `FileUploader`

**Form fields**: Identical to `create.tsx` — name, start_at, end_at, level, venue_id, rule_set_id, timezone, logo, description. All fields pre-populated from `tournament` prop.

**Logo**: `FileUploader` receives `initialPreview={tournament.logo_url ?? undefined}` to show the existing logo.

**Start date**: Calendar does not have a `disabled` guard (unlike the create form — any date can be selected for editing).

**Submit**: `POST /manage/tournaments/{id}` (uses POST for file upload compatibility; backend handles as update). Button label: "Save Changes".

---

### `pages/tournaments/categories/index.tsx` — Category List

**Inertia Props**:
```typescript
interface Props {
    tournament: Tournament;
    categories: TournamentCategory[];
}
```

**Layout**: `AppLayout` wrapping `TournamentLayout` with `activeKey="categories"`

**Breadcrumbs**: Tournaments → `{tournament.name}` → Categories

**Components**: `HeadingSmall`, `DataTable`, `Dialog` (delete confirmation)

**Behaviour**:
- Displays count: `"{n} category/categories in this tournament"`.
- `DataTable` with `getCategoryColumns`; row click navigates to `edit` route.
- Delete flow: row dropdown "Delete" opens confirmation dialog; confirming calls `router.delete('/manage/tournaments/{id}/categories/{id}')`. Warning message states that all registrations will also be removed.

**Navigation**: "Add Category" button links to `/manage/tournaments/{id}/categories/create`.

---

### `pages/tournaments/categories/create.tsx` — Create Category

**Inertia Props**:
```typescript
interface Props {
    tournament: Tournament;
}
```

**Layout**: `AppLayout` (no sub-layout)

**Breadcrumbs**: Tournaments → `{tournament.name}` → Add Category

**Components**: `Heading`, `Input`, `RadioGroup`, `Select`, `DatePicker`

**Form fields**:

| Field | Input type | Notes |
|---|---|---|
| `name` | Text | Optional; placeholder "e.g. Male Cadets -73kg"; auto-generated if blank |
| `gender` | RadioGroup | Male (0) / Female (1); defaults to Male |
| `age_category` | Select | All `AgeCategory` values with label and default age range |
| `min_age` | Number input | Auto-filled when `age_category` is selected |
| `max_age` | Number input | Auto-filled when `age_category` is selected |
| `weight_category` | Select | All `WeightCategory` values with kg label |
| `min_weight` | Number (step 0.01, min 0) | Auto-filled when `weight_category` is selected |
| `max_weight` | Number (step 0.01, min 0) | Auto-filled when `weight_category` is selected |
| `registration_open_at` | DatePicker | Optional scheduling section |
| `registration_close_at` | DatePicker | Optional |
| `start_at` | DatePicker | Optional |
| `end_at` | DatePicker | Optional |
| `weigh_in_start_at` | DatePicker | Optional |
| `weigh_in_end_at` | DatePicker | Optional |

**Auto-fill logic**: Selecting an `age_category` calls `handleAgeCategoryChange`, which finds the matching preset and sets `min_age` and `max_age`. Same pattern for `weight_category` → `handleWeightCategoryChange`.

**Submit**: `POST /manage/tournaments/{id}/categories`. Cancel links to `/manage/tournaments/{id}`.

---

### `pages/tournaments/categories/edit.tsx` — Edit Category (Overview Tab)

**Inertia Props**:
```typescript
interface Props {
    tournament: Tournament;
    category: TournamentCategory;
}
```

**Layout**: `AppLayout` wrapping `CategoryLayout` with `activeKey="overview"`

**Breadcrumbs**: Tournaments → `{tournament.name}` → `{categoryName}` → (implicit from layout)

**Components**: `HeadingSmall`, `Input`, `RadioGroup`, `Select`, `DatePicker`

**Form fields**: Identical to `create.tsx` — name, gender, age_category, min_age, max_age, weight_category, min_weight, max_weight, plus all scheduling date fields.

**Pre-population**: All fields populated from `category` prop. Date fields use `toDateOnly(dateStr)` helper (takes `substring(0, 10)` — YYYY-MM-DD only) to strip time component.

**Submit**: `PUT /manage/tournaments/{id}/categories/{id}`. Button label: "Save Changes".

---

### `pages/tournaments/registrations/index.tsx` — Registrations

**Inertia Props**:
```typescript
interface Props {
    tournament: Tournament;
    category: TournamentCategory;
    registrations: TournamentRegistration[];
    players: Player[];
    clubs: Club[];
}
```

**Layout**: `AppLayout` wrapping `CategoryLayout` with `activeKey="registrations"`

**Breadcrumbs**: Tournaments → `{tournament.name}` → `{categoryName}` → Registrations

**Components**: `HeadingSmall`, `Table` (shadcn), `Dialog`, `Select`, `Avatar`

**Behaviour**:
- Displays all registered players in a table: Player (avatar + name), Club, Age (at registration), Weight (kg).
- Registration is locked when `category.status !== CategoryStatus.Pending` (i.e., 0). When `canRegister` is false, the "Register Player" button and the Actions column are hidden.
- **Register Player dialog**: user selects a `Player` from the `players` prop and a `Club` from the `clubs` prop. Players are shown with their photo, calculated age (from `player.dob`), and weight. Submit is disabled until both are selected.
  - `players` prop contains pre-filtered eligible players (backend responsibility).
  - `clubs` prop contains all available clubs.
- On successful registration, dialog closes and selections are cleared.
- **Remove**: `router.delete('/manage/tournaments/{id}/categories/{id}/registrations/{regId}')`. Only shown when `canRegister` is true.

**Age calculation**: Client-side `calculateAge(dob: string): number` computes current age from date of birth (accounts for whether birthday has passed this year).

**Submit**: `POST /manage/tournaments/{id}/categories/{id}/registrations` with `{ player_id, club_id }`.

---

### `pages/tournaments/matches/index.tsx` — Matches / Bracket

**Inertia Props**:
```typescript
interface Props {
    tournament: Tournament;
    category: TournamentCategory;
    bracket: TournamentBracket | null;
    bracketStructure: BracketStructure | null;
}
```

**Layout**: `AppLayout` wrapping `CategoryLayout` with `activeKey="matches"`

**Breadcrumbs**: Tournaments → `{tournament.name}` → `{categoryName}` → Matches

**Components**: `HeadingSmall`, `Alert`, `Card`, `Badge`, `Dialog`, `Select`, `Button`

**Bracket generation flow**:
1. When `bracket` is `null`, a "Generate Bracket" button is shown.
2. Clicking it opens a dialog where the user selects:
   - **Bracket Type**: `Single Elimination (0)` or `Repechage/IJF (3)`. (Note: `BracketType` has four values but only two are exposed in the UI.)
   - **Seeding Method**: `Random (0)`, `Manual (1)`, or `By Rank (2)`.
3. Confirming calls `POST /manage/tournaments/{id}/categories/{id}/matches/generate` with `{ type: int, seeding_method: int }`.
4. On success the dialog closes and `bracketStructure` is now populated on the next page render.

**Bracket display**:
- When `bracketStructure` is present, all sections (`Main`, `Repechage`, `Bronze`, `Final`) are flattened via `Object.values(bracketStructure.sections).flat()` into a single `allRounds` array.
- Each round is rendered as a group with a section/round label header.
- Each match is a `Card` (64 width). Completed matches are `opacity-75`. Clicking a non-completed match that has both competitors opens the result dialog.
- Within each card: White and Blue competitor rows. The winner's row is highlighted (`bg-primary/10 font-semibold`) and a `Trophy` icon is shown. Seed shown as a badge (`#N`). Score shown if set. Bottom row shows "Completed" or scheduled time or "Not scheduled".

**Match result update flow**:
1. Clicking an eligible match (status not Completed, both `white` and `blue` non-null) opens the "Record Result" dialog.
2. User selects winner by clicking one of two buttons (White player name / Blue player name). `winnerPosition` state is `'0'` (White) or `'1'` (Blue).
3. "Record Result" button (disabled until `winnerPosition` is set) calls `PATCH /manage/tournaments/{id}/categories/{id}/matches/{matchId}/result` with:
   - `winner_position: parseInt(winnerPosition)`
   - `white_score`, `white_penalties`, `blue_score`, `blue_penalties` (from current match state)
4. On success, dialog closes, selected match and winner position are cleared.

---

### `pages/tournaments/results/index.tsx` — Results

**Inertia Props**:
```typescript
interface Props {
    tournament: Tournament;
    category: TournamentCategory;
    results: TournamentResultRow[];
}
```

**Layout**: `AppLayout` wrapping `CategoryLayout` with `activeKey="results"`

**Breadcrumbs**: Tournaments → `{tournament.name}` → `{categoryName}` → Results

**Components**: `HeadingSmall`, `Alert`, `Table`, `Avatar`, `Medal` icon

**Behaviour**:
- Read-only page — no form actions.
- When `results` is empty, shows an `Alert` explaining results are finalized after the bracket is completed.
- Otherwise renders a table: Position (Medal icon + label), Player (avatar + name), Club.

**Position labelling**:
- 1 → "Gold" (yellow Medal icon)
- 2 → "Silver" (slate Medal icon)
- 3 → "Bronze" (amber Medal icon)
- 4+ → `"{n}th"` (muted Medal icon)

Results are populated automatically by the backend as the bracket bracket progresses to completion.

---

## Navigation Map

| Action | URL | HTTP Method |
|---|---|---|
| List tournaments | `/manage/tournaments` | GET |
| Create form | `/manage/tournaments/create` | GET |
| Store tournament | `/manage/tournaments` | POST |
| Tournament overview / edit | `/manage/tournaments/{id}` | GET |
| Update tournament | `/manage/tournaments/{id}` | POST (file upload) |
| Delete tournament | `/manage/tournaments/{id}` | DELETE |
| Category list | `/manage/tournaments/{id}/categories` | GET |
| Create category form | `/manage/tournaments/{id}/categories/create` | GET |
| Store category | `/manage/tournaments/{id}/categories` | POST |
| Edit category (overview tab) | `/manage/tournaments/{id}/categories/{catId}/edit` | GET |
| Update category | `/manage/tournaments/{id}/categories/{catId}` | PUT |
| Delete category | `/manage/tournaments/{id}/categories/{catId}` | DELETE |
| Registrations tab | `/manage/tournaments/{id}/categories/{catId}/registrations` | GET |
| Register player | `/manage/tournaments/{id}/categories/{catId}/registrations` | POST |
| Remove registration | `/manage/tournaments/{id}/categories/{catId}/registrations/{regId}` | DELETE |
| Matches tab | `/manage/tournaments/{id}/categories/{catId}/matches` | GET |
| Generate bracket | `/manage/tournaments/{id}/categories/{catId}/matches/generate` | POST |
| Record match result | `/manage/tournaments/{id}/categories/{catId}/matches/{matchId}/result` | PATCH |
| Results tab | `/manage/tournaments/{id}/categories/{catId}/results` | GET |
