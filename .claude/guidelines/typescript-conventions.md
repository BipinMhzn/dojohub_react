# TypeScript Conventions Guideline

How to use TypeScript in dojohub_react.

---

## Setup

| Setting | Value |
|---------|-------|
| Version | 5.7 |
| Target | ESNext |
| Module | ESNext (bundler resolution) |
| Mode | **strict: true** |
| Path alias | `@/*` → `resources/js/*` |
| Check command | `npm run types` (runs `tsc --noEmit`) |

**Rule:** every PR with TS changes must pass `npm run types` — strict mode catches missing returns, untyped props, undefined access, etc.

---

## `any` is banned. `unknown` is rare.

```tsx
// ❌
function format(value: any) { ... }

// ✅
function format(value: string | number) { ... }

// ✅ rare — use only when truly unknown (e.g. parsed JSON of unknown shape)
function parse(raw: unknown): Venue {
    if (!isVenue(raw)) throw new Error('bad shape');
    return raw;
}
```

If you genuinely can't avoid `any`, leave a comment and prefer `unknown` so the type system forces a narrow.

---

## Page props — explicit types

Declare a `{Page}Props` type at the top of every page file:

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

Use **type** (not `interface`) for page props — they're rarely extended and reading is more consistent.

---

## Entity types (Venue, Club, etc.)

Each entity has a TypeScript type that mirrors the JSON shape returned by the backend (after `onBeforeResult` eager-loading).

```tsx
export type Venue = {
    id: string;          // ULID
    name: string;
    mats_count: number;  // from withCount('mats')
    address: VenueAddress;
    mats?: VenueMat[];   // optional — only present when explicitly loaded
    created_at: string;  // ISO 8601
    updated_at: string;
};

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
```

**Rules:**
- ULIDs are `string`, not a branded type (the cost-benefit isn't worth it here)
- Eager-loaded relationships are optional (`?`) when not always present
- Nullable DB columns are `T | null`, not `T | undefined`
- Datetimes are `string` (ISO 8601), formatted via `date-fns` at render time

---

## Where to put types

| Scope | Location |
|-------|----------|
| Used in 2+ files (multiple pages or components) | `types/index.d.ts` |
| Used in only one page tree (e.g. tournament-specific helpers) | Co-locate in the page's `index.tsx`, re-export |
| Global/ambient (Window declarations, env vars) | `types/global.d.ts` |
| Vite-injected env vars | `types/vite-env.d.ts` |

Reference: `types/index.d.ts` exports `Auth`, `BreadcrumbItem`, `NavItem`, `SharedData`, `User`, `PaginationLink`, `PageFilters`.

---

## `SharedData` and `usePage`

`SharedData` is the type of the props that the Laravel middleware (`HandleInertiaRequests`) shares with **every** page:

```tsx
// types/index.d.ts
export interface SharedData {
    name: string;
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    flash: FlashMessages;
    [key: string]: unknown;  // allows additional shared keys
}

// usage
import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

const { auth, flash, ziggy } = usePage<SharedData>().props;
```

Always type `usePage<SharedData>()` — without it, `.props` is `unknown`.

---

## React-specific typing

### Event handlers

```tsx
const handleSubmit = (e: React.FormEvent) => { ... };
const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => { ... };
const handleClick = (e: React.MouseEvent<HTMLButtonElement>) => { ... };
const handleKey = (e: React.KeyboardEvent<HTMLInputElement>) => { ... };
```

### Component prop types

```tsx
// ✅ Inline type for tiny components
export default function Heading({ title, description }: { title: string; description?: string }) { ... }

// ✅ Named type for larger components
type AppSidebarProps = {
    items: NavItem[];
    isMobile?: boolean;
    onClose?: () => void;
};

export default function AppSidebar({ items, isMobile = false, onClose }: AppSidebarProps) { ... }
```

### Refs

```tsx
const hasMounted = useRef<boolean>(false);
const inputRef = useRef<HTMLInputElement>(null);

// Forwarding refs
const Input = React.forwardRef<HTMLInputElement, InputProps>((props, ref) => { ... });
```

---

## `useForm` typing

`useForm` infers types from the initial value:

```tsx
const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    address_line_1: '',
    country_code: '',
    latitude: '',          // strings, will be parsed server-side
    longitude: '',
});

// data.name is string
// errors.name is string | undefined
```

For nullable values (e.g. file uploads), use type assertion in the initial value:

```tsx
const { data, setData } = useForm({
    logo: null as File | null,
    name: '',
});
```

---

## Optional chaining and nullish coalescing

Use them liberally — strict mode requires defensive access on optional fields:

```tsx
// Optional chaining
const cityName = venue.address?.city;
const country = venue.address?.country?.name ?? 'Unknown';

// Nullish coalescing (??) only triggers on null/undefined, NOT on '' or 0
const search = filters.keyword ?? '';     // ✅
const search = filters.keyword || '';     // ❌ — would also fire on falsy "" deliberately set
```

Prefer `??` over `||` for default values unless you specifically want falsy fallback.

---

## Type assertions

```tsx
// ✅ Limited assertions
const file = e.target.files?.[0] as File | undefined;
const id = params.id as string;

// ❌ Bypass type system
const v = something as any as Venue;   // double-assert = code smell
```

If you find yourself double-asserting, the upstream type is wrong — fix it there.

---

## Enums on the frontend

Backend stores enums as integers (`PlayerGenderEnum::Male = 0`). On the frontend, mirror them either as TypeScript enums OR as `as const` objects:

```tsx
// ✅ Mirror as const object (preferred — no JS class overhead)
export const PlayerGender = {
    Male: 0,
    Female: 1,
    Other: 2,
} as const;
export type PlayerGender = typeof PlayerGender[keyof typeof PlayerGender];

// Then in a component
{gender === PlayerGender.Male && <Badge>Male</Badge>}
```

Keep frontend enum values in sync with `wiki/Enums.md`.

---

## Lucide icons — import as components

```tsx
import { Trash2, Pencil, Plus } from 'lucide-react';

<Button>
    <Plus className="mr-2 h-4 w-4" />
    New Venue
</Button>
```

Icons accept all SVG props plus `size` and `color`. Type as `LucideIcon` when storing in a typed structure:

```tsx
import { LucideIcon } from 'lucide-react';

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
}
```

---

## Pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| `any` slipping in | Run `npm run types`; ban via ESLint |
| Page prop type omitted | Declare a `{Page}Props` type explicitly |
| Reading `usePage().props` without `<SharedData>` | Always `usePage<SharedData>()` |
| Mixing `null` and `undefined` for nullables | Use `null` for "DB nullable", `undefined` for "optional prop" |
| `||` for default value when `''` or `0` is valid | Use `??` |
| Treating ULIDs as numeric | They're strings — never `parseInt` |
| Date strings cast to `Date` everywhere | Parse with `date-fns` at render time only |
| Forgetting `as const` on enum-like objects | Without it, you get `number` instead of the literal |

---

## Related docs

- [react-patterns.md](react-patterns.md) — Component patterns
- [forms-and-validation.md](forms-and-validation.md) — `useForm` typing
- [frontend-architecture.md](frontend-architecture.md) — Folder structure
- `resources/js/types/index.d.ts` — Reference shared types
- `resources/js/pages/venues/index.tsx` — Reference entity types
