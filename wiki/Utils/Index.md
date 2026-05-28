# Utilities

Three utility modules live under `resources/js/utils/` and `resources/js/lib/`. They are plain functions and static data — not React hooks or components.

---

## `utils/date.tsx` — Date Formatting Helpers

**File:** `resources/js/utils/date.tsx`

Three thin wrappers around the native `Date` API. No external library dependency (does not use `date-fns`, despite `date-fns` being in the project for the calendar component).

---

### `formatUTCDate`

```ts
export const formatUTCDate = (dateStr: string | Date): string
```

Formats a UTC date string or `Date` object into a locale-sensitive human-readable date.

**Implementation:**

```ts
return new Date(dateStr).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
});
```

- `locale: undefined` — uses the browser's default locale (e.g. `'en-US'` → `'May 28, 2025'`, `'ne-NP'` → `'२०२५ मे २८'`).
- Output format example (en-US): `"May 28, 2025"`.
- Accepts both ISO strings (`"2025-05-28T00:00:00Z"`) and `Date` instances.

**Usage example:**

```tsx
import { formatUTCDate } from '@/utils/date';

<span>{formatUTCDate(tournament.start_date)}</span>
// → "May 28, 2025"
```

**Typical uses:** tournament start/end dates, player date of birth display, any read-only date cell in a data table.

---

### `convertLocalDateToUTCString`

```ts
export const convertLocalDateToUTCString = (date: Date): string
```

Converts a local `Date` object to a UTC datetime string formatted for MySQL (`"YYYY-MM-DD HH:MM:SS"`).

**Implementation:**

```ts
return new Date(date).toISOString().replace('T', ' ').substring(0, 19);
```

- `toISOString()` produces `"2025-05-28T12:34:56.789Z"` (always UTC).
- `.replace('T', ' ')` → `"2025-05-28 12:34:56.789Z"`.
- `.substring(0, 19)` → `"2025-05-28 12:34:56"` (strips milliseconds and timezone marker).

**Usage example:**

```ts
import { convertLocalDateToUTCString } from '@/utils/date';

// User picks a date via the date picker (local time)
const utcForBackend = convertLocalDateToUTCString(selectedDate);
// → "2025-05-28 00:00:00"

router.post(route('tournaments.store'), { start_date: utcForBackend });
```

**Typical uses:** form submission — converting a user-selected `Date` (from a date picker) into the string format the Laravel backend stores in MySQL `DATETIME` columns.

---

### `convertUTCStringToLocalDate`

```ts
export const convertUTCStringToLocalDate = (dateStr: string): Date
```

Parses a UTC datetime string from the API into a local `Date` object.

**Implementation:**

```ts
return new Date(dateStr);
```

The native `Date` constructor parses ISO 8601 strings and interprets them as UTC when the `Z` suffix is present, or as local time when absent. Strings from a MySQL `DATETIME` column (no timezone info) are typically passed as-is; the function wraps the constructor for consistent import convention.

**Usage example:**

```ts
import { convertUTCStringToLocalDate } from '@/utils/date';

// Pre-fill a date picker with an existing record's value
const localDate = convertUTCStringToLocalDate(tournament.start_date);
// → Date object in local timezone
```

**Typical uses:** pre-populating date picker components on edit forms.

---

## `utils/countries.tsx` — Static Geographic Data

**File:** `resources/js/utils/countries.tsx`

Exports a single static array used to populate province/district dropdowns in forms. Data is loaded directly from this file — no API call.

### Signature

```ts
import { Country } from '@/types';
export const countries: Country[];
export default countries;
```

### Data structure

```ts
// Country → provinces → districts
countries = [
    {
        name: 'Nepal',
        states: [                         // "states" = provinces
            {
                name: 'Koshi Province',
                cities: [                 // "cities" = districts
                    'Bhojpur', 'Dhankuta', 'Ilam', ...  // 14 districts
                ],
            },
            { name: 'Madhesh Province',   cities: [ ... ] },  //  8 districts
            { name: 'Bagmati Province',   cities: [ ... ] },  // 13 districts
            { name: 'Gandaki Province',   cities: [ ... ] },  // 11 districts
            { name: 'Lumbini Province',   cities: [ ... ] },  // 12 districts
            { name: 'Karnali Province',   cities: [ ... ] },  // 10 districts
            { name: 'Sudurpashchim Province', cities: [ ... ] }, // 9 districts
        ],
    },
];
```

**Nepal's 7 provinces with their district counts:**

| Province | Districts |
|---|---|
| Koshi | 14 |
| Madhesh | 8 |
| Bagmati | 13 |
| Gandaki | 11 |
| Lumbini | 12 |
| Karnali | 10 |
| Sudurpashchim | 9 |

**Note:** The `Country` type uses `states` for provinces and `cities` for districts. This is a generic naming convention — the actual data represents Nepal's administrative divisions, not generic countries/states/cities.

### Current scope

Only Nepal is included. The array has a single element. If international support is needed in the future, additional country objects can be appended; no interface change is required.

### Usage example

```tsx
import { countries } from '@/utils/countries';

// Province select options
const provinces = countries[0].states;

// District select options after a province is selected
const districts = selectedProvince?.cities ?? [];
```

**Typical uses:** player/coach/club/venue address forms that need province and district dropdowns.

---

## `lib/utils.ts` — `cn()` Class Merging Helper

**File:** `resources/js/lib/utils.ts`

```ts
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}
```

### Purpose

A one-liner that combines two libraries to produce a clean Tailwind class string:

- **`clsx`** — accepts any mix of strings, arrays, objects, `undefined`, `false`, etc. and concatenates the truthy class names.
- **`twMerge`** — deduplicates conflicting Tailwind utilities. For example, `twMerge('px-2 px-4')` → `'px-4'` (last wins); `twMerge('text-red-500 text-blue-500')` → `'text-blue-500'`.

Together they allow conditional class composition without worrying about utility conflicts.

### Signature

```ts
function cn(...inputs: ClassValue[]): string
```

`ClassValue` (from `clsx`) accepts:
- `string`
- `string[]`
- `Record<string, boolean>` — `{ 'bg-muted': isActive }`
- `null | undefined | false` — ignored

### Usage examples

**Conditional active state (layout nav):**

```tsx
className={cn('w-full justify-start', {
    'bg-muted': activeKey === item.key,
})}
```

**Variant merging in a component:**

```tsx
className={cn(
    'rounded-md border px-4 py-2 text-sm',
    variant === 'destructive' && 'border-red-500 bg-red-50 text-red-700',
    className,   // caller override always wins
)}
```

**Falsy values are safe:**

```tsx
cn('base', undefined, null, false, 'extra')
// → "base extra"
```

### Where it's used

`cn` is used in virtually every component in the codebase — all shadcn/ui primitives in `components/ui/`, all domain layout files, and most page components that need conditional classes.

---

## Related

- [Frontend Overview](../Frontend/Index.md)
- [Types — Country / State interfaces](../Types/Index.md)
- [Hooks — useAppearance](../Hooks/Index.md)
