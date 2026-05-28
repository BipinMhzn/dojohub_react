# React Patterns Guideline

How to write React components in dojohub_react.

---

## Component shape — default export, typed props

Every component:
- Has a `default export` (pages) or `named export` (utilities)
- Declares an explicit props interface or inline type
- Uses function declaration syntax (`function Component(...)`) — not arrow function variables

```tsx
// ✅ Page (default export, named function)
export default function Venues({ venues, filters }: VenuesProps) {
    return ...;
}

// ✅ Component (default export, named function)
export default function Heading({ title, description }: { title: string; description?: string }) {
    return ...;
}

// ✅ Hook / utility (named export, no JSX in name)
export function useFlashToast(): void { ... }
```

Use inline prop types for tiny components (1–3 props). Lift to a named interface when the shape is reused or larger.

---

## Props from Inertia — typed page props

Every page receives props from a Laravel controller (`Inertia::render('venues/index', [...])`). Declare them at the top of the page file:

```tsx
type VenuesProps = {
    venues: { data: Venue[]; links: PaginationLink[] };
    filters: {
        keyword?: string;
        sort_by?: string;
        sort_order?: string;
        page?: number;
        per_page?: number;
    };
};

export default function Venues({ venues, filters }: VenuesProps) { ... }
```

For props shared across all pages (auth user, ziggy config, flash), use `usePage<SharedData>().props` instead — `SharedData` lives in `types/index.d.ts`.

```tsx
import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

const { auth, flash } = usePage<SharedData>().props;
```

---

## Where types for entities live

| Type scope | Location |
|------------|----------|
| Entity used in only one page tree (e.g. `Venue` shape) | Co-located at the top of `pages/{module}/index.tsx` and re-exported |
| Entity used across multiple modules | Lift to `types/index.d.ts` |
| Ambient/global types | `types/global.d.ts` |
| Page-specific types | Inline in the page file |

Reference: `pages/venues/index.tsx` exports `Venue`, `VenueAddress`, `VenueMat`, `Country` for sibling files to import.

---

## Navigation — `<Link>`, never raw `<a>`

```tsx
import { Link } from '@inertiajs/react';

// ✅ SPA-style navigation
<Link href="/manage/venues/create">New Venue</Link>
<Link href={route('venues.show', venue.id)}>{venue.name}</Link>

// ✅ Button-as-link via shadcn/ui `asChild`
<Button asChild>
    <Link href="/manage/venues/create">New Venue</Link>
</Button>

// ❌ Loses Inertia state, full page reload
<a href="/manage/venues/create">New Venue</a>
```

Use **Ziggy's `route()`** for typed route names whenever possible. Ziggy is configured in `app.tsx` and types are pulled from `tsconfig.json`.

---

## Imperative navigation — `router`

For programmatic visits (after a confirmation, post-delete, etc.):

```tsx
import { router } from '@inertiajs/react';

router.visit('/manage/venues');                          // GET
router.get('/manage/venues', params, { preserveState: true });
router.post('/manage/clubs', data, { forceFormData: true });
router.put(`/manage/venues/${id}`, data);
router.delete(`/manage/venues/${id}`);

// Options
router.get(url, params, {
    only: ['venues'],          // Partial reload — only `venues` prop refetched
    preserveState: true,       // Keep local component state
    preserveScroll: true,      // Don't scroll to top
    replace: true,             // History.replace instead of push
});
```

For form submissions, prefer `useForm` over `router.post` — it handles validation errors automatically.

---

## State — locality first

```
URL state (Inertia query params)  ←  filters, pagination, sort
Component state (useState)        ←  UI-only state (modal open/close, debounce)
Page props (Inertia)              ←  Server-derived data
Shared props (usePage)            ←  Auth user, flash messages, ziggy
```

**Don't** add a global state library (Redux, Zustand, etc.). The Inertia + URL pattern is sufficient for dojohub_react's needs.

### URL state pattern (filters, sort, pagination)

The standard list-page pattern:

```tsx
const [search, setSearch] = useState(filters.keyword ?? '');
const [sortBy, setSortBy] = useState(filters.sort_by ?? 'name');
const [sortOrder, setSortOrder] = useState(filters.sort_order ?? 'asc');
const [pageNumber, setPageNumber] = useState(filters.page ?? 1);
const [perPage, setPerPage] = useState(filters.per_page ?? 10);
const [debouncedSearch, setDebouncedSearch] = useState(search);
const hasMounted = useRef(false);

useEffect(() => {
    const handler = setTimeout(() => setDebouncedSearch(search), 500);
    return () => clearTimeout(handler);
}, [search]);

useEffect(() => {
    if (!hasMounted.current) { hasMounted.current = true; return; }
    router.get('/manage/venues', {
        keyword: debouncedSearch, sort_by: sortBy, sort_order: sortOrder,
        page: pageNumber, per_page: perPage,
    }, { only: ['venues'], preserveState: true, preserveScroll: true, replace: true });
}, [debouncedSearch, sortBy, sortOrder, pageNumber, perPage]);
```

Reference: `pages/venues/index.tsx`. Reuse this pattern for any list page.

---

## Flash messages — toasts

Backend redirects with `->with('success', ...)` or `->with('error', ...)`. The `useFlashToast` hook turns these into sonner toasts:

```tsx
import { useFlashToast } from '@/hooks/use-flash-toast';

// Call once in the top-level layout
useFlashToast();
```

Hook is wired into `AppLayout` — every page inside it gets toasts automatically. Don't call it manually in pages.

---

## Conditional rendering

```tsx
// ✅ Short-circuit for "show if truthy"
{description && <p className="...">{description}</p>}

// ✅ Ternary for either/or
{processing ? 'Saving...' : 'Save'}

// ✅ Nullish coalescing in JSX
<Input value={data.name ?? ''} />
```

Avoid deeply nested ternaries — extract to a function or `if/return` blocks.

---

## Loops — keys matter

```tsx
// ✅ Stable key from data
{countries.map((c) => (
    <SelectItem key={c.code} value={c.code}>{c.name}</SelectItem>
))}

// ❌ Index as key (breaks reordering)
{items.map((item, i) => <li key={i}>{item}</li>)}
```

Use the entity's `id` (or natural unique field like `code`) as the key. Only fall back to index when the list is static and never reordered.

---

## shadcn/ui composition

`components/ui/` holds shadcn/ui primitives — Radix UI under the hood, styled with Tailwind. Compose them in your pages/components, don't fork the primitives:

```tsx
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

<Dialog open={!!venueToDelete} onOpenChange={(open) => !open && setVenueToDelete(null)}>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Delete venue?</DialogTitle>
            <DialogDescription>This action cannot be undone.</DialogDescription>
        </DialogHeader>
        <DialogFooter>
            <Button variant="outline" onClick={() => setVenueToDelete(null)}>Cancel</Button>
            <Button variant="destructive" onClick={handleConfirmDelete}>Delete</Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
```

If shadcn's defaults aren't enough, **extend** them via `className` + `cn()` helper, don't rewrite.

---

## Tailwind & `cn()` for conditional classes

```tsx
import { cn } from '@/lib/utils';

<button className={cn(
    'rounded px-4 py-2',
    isActive ? 'bg-primary text-white' : 'bg-secondary text-gray-700',
    disabled && 'cursor-not-allowed opacity-50',
)}>
```

`cn()` is a `clsx` + `tailwind-merge` wrapper — handles class conflicts (e.g. `px-4` overriding `px-2`) intelligently.

---

## Effects — minimize and clean up

```tsx
useEffect(() => {
    const handler = setTimeout(() => setDebouncedSearch(search), 500);
    return () => clearTimeout(handler);  // cleanup is mandatory
}, [search]);
```

Avoid effects for derived state. Compute inline instead:

```tsx
// ✅
const totalRegistered = registrations.length;

// ❌ — derived from props, no need for an effect
const [totalRegistered, setTotal] = useState(0);
useEffect(() => setTotal(registrations.length), [registrations]);
```

---

## Lifting state vs co-locating

| Heuristic | Action |
|-----------|--------|
| State used by one component only | `useState` inside that component |
| Shared between sibling components | Lift to the common parent |
| Shared across unrelated pages | URL state (Inertia query) — NOT a context |
| Auth user / flash | `usePage<SharedData>()` — already shared |

Avoid creating React Contexts unless the value crosses 3+ component layers. Most "shared" needs are solved by Inertia shared props or URL state.

---

## Pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| `<a href>` for internal navigation | Use `<Link>` from `@inertiajs/react` |
| `fetch()` for mutations | Use `useForm` or `router.post/put/delete` |
| Storing server data in `useState` | It's already in props — let Inertia partial reload refresh it |
| Forgetting cleanup in `setTimeout`/`setInterval` effects | Return a cleanup function |
| Using `index` as React key in dynamic lists | Use entity ID |
| Creating ad-hoc components inside a page render | Move to `partials/{name}.tsx` or `components/` |
| Forgetting `forceFormData: true` when the form has a File | File silently dropped — see [file-uploads.md](file-uploads.md) |
| Reading server data via `axios`/`fetch` from inside the page | All data comes via Inertia props |

---

## Related docs

- [frontend-architecture.md](frontend-architecture.md) — Folder structure and where things go
- [typescript-conventions.md](typescript-conventions.md) — Type rules
- [forms-and-validation.md](forms-and-validation.md) — Forms with `useForm`
- [wiki/Frontend.md](../../wiki/Frontend.md) — Full frontend reference
- `resources/js/pages/venues/index.tsx` — Reference list page pattern
- `resources/js/hooks/use-flash-toast.ts` — Reference custom hook
