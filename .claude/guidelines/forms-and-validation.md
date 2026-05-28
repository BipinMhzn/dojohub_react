# Forms & Validation Guideline

How to build forms in dojohub_react. The backend validates via Form Requests; the frontend uses Inertia's `useForm` hook.

---

## Two-layer validation

```
┌────────────────────────────────────────────┐
│ Frontend: useForm()                        │
│   - Tracks data, errors, processing state  │
│   - Surfaces backend errors automatically  │
│   - Optional: client-side checks (rare)    │
└────────────────────────────────────────────┘
           │ submit
           ▼
┌────────────────────────────────────────────┐
│ Backend: VenueCreateRequest (Form Request) │
│   - rules() defines validation             │
│   - Auto-redirects with errors on failure  │
│   - Authorize via authorize() (optional)   │
└────────────────────────────────────────────┘
```

The backend is the source of truth. Client-side validation is optional sugar (e.g. disable submit when empty); the real check happens server-side.

---

## Backend — Form Request

Every controller action that mutates data takes a Form Request, never `Request`.

```php
namespace App\Modules\Venues\Requests;

use BaseSystem\Requests\BaseWebRequest;

class VenueCreateRequest extends BaseWebRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'address_line_1' => ['required', 'string', 'max:250'],
            'address_line_2' => ['nullable', 'string', 'max:250'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
```

**Rules:**
- Extends `BaseWebRequest` (for Inertia routes) or `BaseWebListRequest` (for paginated indexes)
- Rules are **array form** (not pipe-delimited string) — allows interleaving rule objects
- Update requests extend Create requests unless rules differ: `class VenueUpdateRequest extends VenueCreateRequest {}`
- Never put `$request->validate(...)` inline in a controller

```php
// ✅
public function store(VenueCreateRequest $request): RedirectResponse { ... }

// ❌
public function store(Request $request): RedirectResponse {
    $request->validate([...]);  // never inline
}
```

---

## Frontend — `useForm`

```tsx
import { Head, useForm } from '@inertiajs/react';

const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    state: '',
    postal_code: '',
    country_code: '',
    latitude: '',
    longitude: '',
});

const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/manage/venues', {
        preserveScroll: true,
        onSuccess: () => reset(),
    });
};
```

| Returned value | Type | Purpose |
|----------------|------|---------|
| `data` | inferred from initial | Current form values |
| `setData(key, value)` | function | Update one field |
| `post/put/patch/delete(url, options)` | function | Submit (returns Promise) |
| `processing` | boolean | True during request — use to disable buttons |
| `errors` | `Partial<Record<keyof data, string>>` | Validation errors from backend |
| `reset()` | function | Reset to initial values |
| `clearErrors()` | function | Clear current errors |
| `progress` | object | Upload progress (for files) |

Reference: `resources/js/pages/venues/create.tsx`.

---

## Field pattern — Label + Input + InputError

The standard field looks like:

```tsx
<div>
    <Label htmlFor="name">Venue Name</Label>
    <Input
        id="name"
        value={data.name}
        className="mt-1 block w-full"
        onChange={(e) => setData('name', e.target.value)}
        disabled={processing}
    />
    <InputError message={errors.name} />
</div>
```

Components used:
- `<Label>` from `@/components/ui/label`
- `<Input>` from `@/components/ui/input`
- `<InputError>` from `@/components/input-error` — renders nothing when `message` is undefined

The same pattern applies for `<Select>` (shadcn), `<Textarea>`, checkboxes, radios.

---

## Select (dropdowns)

```tsx
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

<div>
    <Label htmlFor="country_code">Country</Label>
    <Select
        value={data.country_code}
        onValueChange={(v) => setData('country_code', v)}
        disabled={processing}
    >
        <SelectTrigger className="mt-1 w-full">
            <SelectValue placeholder="Select a country" />
        </SelectTrigger>
        <SelectContent>
            {countries.map((c) => (
                <SelectItem key={c.code} value={c.code}>{c.name}</SelectItem>
            ))}
        </SelectContent>
    </Select>
    <InputError message={errors.country_code} />
</div>
```

`onValueChange` receives the value directly (no event wrapping).

---

## Submit options

```tsx
post('/manage/venues', {
    preserveScroll: true,         // don't scroll to top on success
    preserveState: false,         // reload component state (default for POST)
    forceFormData: true,          // required when form has File fields
    onBefore: () => { ... },      // optional, return false to abort
    onStart: () => { ... },       // before sending
    onProgress: (progress) => { ... },  // file upload progress
    onSuccess: (page) => reset(),
    onError: (errors) => { ... }, // also auto-populates errors
    onFinish: () => { ... },      // always runs
});
```

For PUT/PATCH/DELETE: `put(url, options)`, `patch(url, options)`, `delete(url, options)`.

---

## File uploads + Form

When the form includes a File, set `forceFormData: true` so Inertia sends multipart:

```tsx
const { data, setData, post, processing, errors } = useForm({
    name: '',
    logo: null as File | null,
});

const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setData('logo', e.target.files?.[0] ?? null);
};

const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/manage/clubs', { forceFormData: true });
};
```

See [file-uploads.md](file-uploads.md) for backend handling.

---

## POST-for-update (file uploads)

Backend modules with file uploads use POST for updates (HTML form limitation). Use `router.post` with `_method: 'put'` OR Inertia's spoofing:

```tsx
post(`/manage/clubs/${club.id}`, {
    forceFormData: true,
    // _method handled by Laravel automatically via the route
});
```

Or via `useForm` with explicit method spoofing:

```tsx
const form = useForm({ _method: 'put' as const, name: '', logo: null as File | null });
form.post(`/manage/clubs/${club.id}`, { forceFormData: true });
```

The route is defined as `Route::post('clubs/{club}', ...)`, so Laravel reads `_method` and treats it as PUT for routing.

---

## Submit button — disable during processing

```tsx
<Button type="submit" disabled={processing}>
    {processing ? 'Saving...' : 'Save'}
</Button>
```

Always show *some* indicator (text change, spinner, or both). Users will double-click otherwise.

---

## Cancel button — link, not form action

```tsx
<Button type="button" variant="outline" asChild>
    <Link href="/manage/venues">Cancel</Link>
</Button>
```

`type="button"` is mandatory inside `<form>` — without it, the browser interprets it as a submit button.

---

## Editing — pre-populate from props

```tsx
type EditVenueProps = { venue: Venue; countries: Country[] };

export default function EditVenue({ venue, countries }: EditVenueProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: venue.name,
        address_line_1: venue.address.address_line_1,
        address_line_2: venue.address.address_line_2 ?? '',
        city: venue.address.city,
        country_code: venue.address.country_code,
        // ... convert null to '' for input compatibility
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/manage/venues/${venue.id}`, { preserveScroll: true });
    };

    return <form onSubmit={handleSubmit}>{ /* ... */ }</form>;
}
```

Convert `null` from props to `''` for `<Input>` — controlled inputs can't accept `null`.

---

## Client-side validation (rare)

For better UX (e.g. disabling submit on empty required fields), do it in TypeScript — but **never** as a replacement for backend validation:

```tsx
const isFormValid = data.name.trim().length > 0 && data.country_code.length === 2;

<Button type="submit" disabled={processing || !isFormValid}>Save</Button>
```

For complex rules (regex, multi-field), keep server-side only — duplication is a maintenance trap.

---

## Showing flash messages after submit

`useForm.onSuccess` runs **after** the Inertia redirect resolves. Flash messages set by the backend (`->with('success', ...)`) are surfaced automatically by `useFlashToast` (wired in `AppLayout`) — no manual handling needed.

If you need to do something *inside* the page on success (e.g. close a dialog), use `onSuccess`:

```tsx
post('/manage/venues', {
    onSuccess: () => {
        setDialogOpen(false);
        reset();
    },
});
```

---

## Pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| `$request->validate(...)` inline | Always use a Form Request |
| Forgetting `forceFormData: true` when form has File | File silently dropped |
| `<Input value={data.field ?? ''}>` with null state | Convert null to '' at form-init time, not at render |
| Cancel button without `type="button"` | Browser submits the form on click |
| Submitting via `fetch()` instead of `post()` | Loses Inertia state, errors won't surface |
| Showing errors manually instead of `<InputError />` | Use the component — it handles undefined gracefully |
| `errors.field` typed as `any` | `errors` is typed by `useForm`'s data shape automatically |
| Disabling submit without showing why | Add `aria-disabled` and a tooltip / hint |

---

## Related docs

- [react-patterns.md](react-patterns.md) — Component patterns
- [typescript-conventions.md](typescript-conventions.md) — `useForm` typing
- [error-handling.md](error-handling.md) — Backend validation errors
- [file-uploads.md](file-uploads.md) — File upload patterns
- `resources/js/pages/venues/create.tsx` — Reference form
- `resources/js/components/input-error.tsx` — Error message component
