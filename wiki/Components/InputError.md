# InputError, Heading, and HeadingSmall

Quick reference for the three simplest display components used throughout the application.

---

## InputError

**File:** `resources/js/components/input-error.tsx`

### Props

```ts
// HTMLAttributes<HTMLParagraphElement> & { message?: string }
{
    message?: string;    // the validation error text to display
    className?: string;  // merged with default classes via cn()
    // ...all other <p> HTML attributes
}
```

### Behaviour

- Renders a `<p>` styled `text-xs text-red-600 dark:text-red-400` when `message` is a non-empty string.
- Returns `null` when `message` is `undefined`, `null`, or `''` — safe to render unconditionally below every form field.
- Additional HTML attributes (e.g. `id`, `aria-live`) pass through to the `<p>`.

### When to use

Place immediately below any `<Input>`, `<Select>`, or `<Textarea>` that is tied to an Inertia `useForm` field. Read the error from `errors.<fieldName>`:

```tsx
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';

function CreateTournamentForm() {
    const { data, setData, post, errors, processing } = useForm({
        name: '',
        start_at: '',
    });

    return (
        <form onSubmit={(e) => { e.preventDefault(); post('/manage/tournaments'); }}>
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    aria-invalid={!!errors.name}
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="start_at">Start Date</Label>
                <Input
                    id="start_at"
                    type="date"
                    value={data.start_at}
                    onChange={(e) => setData('start_at', e.target.value)}
                />
                <InputError message={errors.start_at} />
            </div>

            <Button type="submit" disabled={processing}>Create</Button>
        </form>
    );
}
```

Also used inside `FileUploader` (`components/ui/file-uploader.tsx`) to show client-side size validation errors.

---

## Heading

**File:** `resources/js/components/heading.tsx`

### Props

```ts
{
    title: string;
    description?: string;
}
```

### Behaviour

Renders a `<div className="mb-8 space-y-0.5">` containing:
- `<h2 className="text-xl font-semibold tracking-tight">` with `title`
- `<p className="text-muted-foreground text-sm">` with `description` (omitted when not provided)

### When to use

Use at the top of a full page section — typically inside `AppLayout` before a list or form. Example from the Tournaments index:

```tsx
import Heading from '@/components/heading';

<Heading title="Tournaments" description="Manage your tournaments" />
```

---

## HeadingSmall

**File:** `resources/js/components/heading-small.tsx`

### Props

```ts
{
    title: string;
    description?: string;
}
```

### Behaviour

Renders a `<header>` containing:
- `<h3 className="mb-0.5 text-base font-medium">` with `title`
- `<p className="text-muted-foreground text-sm">` with `description` (omitted when not provided)

### When to use

Use for sub-sections within a page — smaller visual weight than `Heading`. Commonly used in:
- Settings sub-pages (e.g. "Delete account")
- Category/nested content areas within a page

```tsx
import HeadingSmall from '@/components/heading-small';

<HeadingSmall
    title="Delete account"
    description="Delete your account and all of its resources"
/>
```

### Heading vs HeadingSmall

| | Heading | HeadingSmall |
|--|---------|-------------|
| Element | `<h2>` | `<h3>` |
| Font size | `text-xl` | `text-base` |
| Font weight | `font-semibold` | `font-medium` |
| Margin bottom | `mb-8` | — (inside `<header>`) |
| Use case | Top of page/section | Sub-section within a page |
