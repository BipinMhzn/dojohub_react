# DataTable Components

Three components that together implement paginated, sortable, searchable list pages. All state is reflected in the URL via Inertia `router.get`, so pages are shareable and browser-navigable.

---

## Overview

```
DataTableControls   — toolbar: search input + sort-by dropdown + asc/desc toggle + optional status filter
DataTable           — table body rendered by TanStack Table
DataTablePagination — Laravel paginator links + per-page selector
```

The page component owns all state (search, sortBy, sortOrder, pageNumber, perPage) and uses Inertia partial reload (`only: ['<resource>']`) to refetch only the list data when any filter changes.

---

## PaginationLink

Defined in `resources/js/types/index.d.ts`:

```ts
export interface PaginationLink {
    label: string;       // "1", "2", "Next »", "« Previous", etc.
    url: string | null;  // null when the link is disabled (prev on page 1, next on last page)
    active: boolean;     // true for the current page
}
```

Laravel's `->links()` / paginator `links` array maps directly to this shape.

---

## DataTableControls

**File:** `resources/js/components/ui/data-table-controls.tsx`

### Props

```ts
type Options = Record<string, string>; // { fieldName: 'Display Label' }

interface DataTableControlsProps {
    search: string;
    onSearchChange: (value: string) => void;

    sortBy: string;           // current sort field key
    sortOrder: string;        // 'asc' | 'desc'
    onSortByChange: (value: string) => void;
    onSortOrderChange: (value: string) => void;
    sortOptions: Options;     // e.g. { name: 'Name', updated_at: 'Last Updated' }

    // Optional status/filter dropdown
    statusOptions?: Options;  // e.g. { '': 'All', '1': 'Active', '0': 'Inactive' }
    statusValue?: string;
    onStatusChange?: (value: string) => void;
}
```

### Behaviour

- The **search input** (`max-w-sm`) calls `onSearchChange` on every keystroke. The page component is responsible for debouncing (typically 500 ms) before triggering a router call.
- The **Sort By dropdown** (right-aligned, outline button showing current label) calls `onSortByChange` with the field key. The current label is read from `sortOptions[sortBy]`.
- The **asc/desc toggle group** uses `ToggleGroup type="single"` with `SortAscendingIcon` / `SortDescendingIcon`. Selecting the already-active value is a no-op (guarded by `value && onSortOrderChange(value)`).
- The **status filter dropdown** (with a `Filter` icon) is rendered only when all three optional props are provided. A thin vertical divider separates it from the sort controls.

---

## DataTable

**File:** `resources/js/components/ui/data-table.tsx`

### Props

```ts
interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    onRowClick?: (row: TData) => void;
}
```

### Behaviour

- Uses TanStack Table v8 (`@tanstack/react-table`) with `getCoreRowModel`.
- Sorting, pagination, and filtering are **server-side** — TanStack Table only handles rendering. No `getSortedRowModel` or `getPaginationRowModel` is configured.
- When `onRowClick` is provided every `<TableRow>` gets `cursor-pointer` and fires the callback with `row.original`.
- Empty state renders a single row spanning all columns with the text "No results."

### Column definition pattern

Use TanStack's `ColumnDef<T>` imported from `@tanstack/react-table`. The header can be a plain string or a render function for sortable headers. The cell renderer receives `info: CellContext<T, V>`.

```ts
import { ColumnDef } from '@tanstack/react-table';

const columns: ColumnDef<Tournament>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => <span className="font-medium">{row.original.name}</span>,
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => <StatusBadge status={row.original.status} />,
    },
    {
        id: 'actions',
        header: '',
        cell: ({ row }) => (
            <Button variant="ghost" size="icon" onClick={() => onDelete(row.original)}>
                <Trash2 className="size-4" />
            </Button>
        ),
    },
];
```

Columns are typically defined in a separate `partials/columns.tsx` file and receive callbacks (e.g. `onDelete`) via a factory function:

```ts
// partials/columns.tsx
export function getTournamentColumns({ onDelete }: { onDelete: (t: Tournament) => void }) {
    return [...] as ColumnDef<Tournament>[];
}
```

---

## DataTablePagination

**File:** `resources/js/components/ui/data-table-pagination.tsx`

### Props

```ts
type PaginationProps = {
    links: PaginationLink[];           // Laravel paginator links array
    perPage: number;                   // current per-page count
    perPageSelect?: number[];          // defaults to [10, 50, 100, 200]
    onPageChange: (page: number) => void;
    onPerPageCountUpdated: (perPage: number) => void;
};
```

### Behaviour

- Parses the page number from each link's `url` query string (`?page=N`).
- When `links.length <= 3` (only prev/page1/next or similar), the prev/page buttons are hidden and only the per-page selector is shown in a right-aligned column.
- The **prev button** (`ChevronLeft`) is only rendered when `links[0].url` is non-null (i.e. there is a previous page).
- The **next button** (`ChevronRight`) is only rendered when `links[links.length - 1].url` is non-null.
- Active page button is styled `variant="secondary"` and disabled.
- The **per-page dropdown** fires `onPerPageCountUpdated` only when the selected value differs from the current `perPage`.

---

## How the three components wire together

The page component owns all state and uses a single `useEffect` to call `router.get` with all filter params whenever any value changes. A `hasMounted` ref prevents the initial render from triggering a redundant request.

```tsx
// resources/js/pages/tournaments/index.tsx (simplified pattern)

const sortOptions = { name: 'Name', updated_at: 'Last Updated' };

const [search, setSearch] = useState(filters.keyword ?? '');
const [sortBy, setSortBy] = useState(filters.sort_by ?? 'name');
const [sortOrder, setSortOrder] = useState(filters.sort_order ?? 'asc');
const [pageNumber, setPageNumber] = useState(filters.page ? Number(filters.page) : 1);
const [perPage, setPerPage] = useState(filters.per_page ? Number(filters.per_page) : 10);
const [debouncedSearch, setDebouncedSearch] = useState(search);

// Debounce the search input by 500 ms
useEffect(() => {
    const handler = setTimeout(() => setDebouncedSearch(search), 500);
    return () => clearTimeout(handler);
}, [search]);

// Trigger a partial Inertia reload whenever any filter changes
const hasMounted = useRef(false);
useEffect(() => {
    if (!hasMounted.current) { hasMounted.current = true; return; }
    router.get('/manage/tournaments', {
        keyword: debouncedSearch,
        sort_by: sortBy,
        sort_order: sortOrder,
        page: pageNumber,
        per_page: perPage,
    }, { only: ['tournaments'], preserveState: true, preserveScroll: true, replace: true });
}, [debouncedSearch, sortBy, sortOrder, pageNumber, perPage]);

// Reset to page 1 when a filter changes
const handleSearchChange = (key: string) => { setSearch(key); setPageNumber(1); };
const handleSortByChange = (key: string) => { setSortBy(key); setPageNumber(1); };

return (
    <>
        <DataTableControls
            search={search}
            onSearchChange={handleSearchChange}
            sortBy={sortBy}
            sortOrder={sortOrder}
            onSortByChange={handleSortByChange}
            onSortOrderChange={setSortOrder}
            sortOptions={sortOptions}
        />
        <DataTable
            columns={columns}
            data={data}
            onRowClick={(row) => router.visit(`/manage/tournaments/${row.id}`)}
        />
        <DataTablePagination
            links={links}
            perPage={perPage}
            onPageChange={setPageNumber}
            onPerPageCountUpdated={(p) => { setPerPage(p); setPageNumber(1); }}
        />
    </>
);
```

### URL params used

| Param | Source | Default |
|-------|--------|---------|
| `keyword` | `DataTableControls` search input (debounced) | `''` |
| `sort_by` | `DataTableControls` sort-by dropdown | resource-specific (e.g. `'name'`) |
| `sort_order` | `DataTableControls` asc/desc toggle | `'asc'` |
| `page` | `DataTablePagination` prev/next/page buttons | `1` |
| `per_page` | `DataTablePagination` per-page dropdown | `10` |
| `status` | `DataTableControls` optional status dropdown | resource-specific |

The Laravel controller reads these from the request and returns them back as `filters` in the Inertia props so state is restored on browser back/forward.

---

## PageFilters type

`resources/js/types/index.d.ts` exports `PageFilters` as a convenience type for the `filters` prop shape:

```ts
export interface PageFilters {
    keyword?: string;
    sort_by?: string;
    sort_order?: string;
    page?: number;
    per_page?: number;
    status?: string;
}
```
