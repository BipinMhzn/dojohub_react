# HandleInertiaRequests Middleware — Wiki

**File**: `app/Http/Middleware/HandleInertiaRequests.php`  
**Extends**: `Inertia\Middleware`  
**Applied to**: All web routes (via `web` middleware group)

---

## Purpose

Central location for all data shared with every Inertia page. Overrides `share()` to inject props that are available in every React component through `usePage().props`.

---

## Shared Props

### Full `share()` return value

```php
return [
    ...parent::share($request),    // Inertia version string
    'name'       => config('app.name'),
    'quote'      => ['message' => trim($message), 'author' => trim($author)],
    'auth'       => ['user' => $request->user()],
    'ziggy'      => fn (): array => [...(new Ziggy)->toArray(), 'location' => $request->url()],
    'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
    'flash'       => [
        'success' => fn () => $request->session()->get('success'),
        'error'   => fn () => $request->session()->get('error'),
        'info'    => fn () => $request->session()->get('info'),
        'warning' => fn () => $request->session()->get('warning'),
    ],
];
```

---

## Prop Reference

| Prop | Type | Source | Notes |
|------|------|--------|-------|
| `name` | `string` | `config('app.name')` | App name (DojoHub) — used in page `<title>` |
| `quote.message` | `string` | `Inspiring::quotes()->random()` split by `\n` | Random inspirational quote |
| `quote.author` | `string` | Same quote split | Author of the quote |
| `auth.user` | `User\|null` | `$request->user()` | Authenticated user or null |
| `ziggy` | `object` | `new Ziggy()` + `location` | Named route definitions for `route()` helper in JS |
| `sidebarOpen` | `boolean` | `sidebar_state` cookie | `true` if cookie is absent or `'true'` |
| `flash.success` | `string\|null` | Session key `success` | Set in controllers via `redirect()->with('success', ...)` |
| `flash.error` | `string\|null` | Session key `error` | Set via `redirect()->with('error', ...)` |
| `flash.info` | `string\|null` | Session key `info` | Set via `redirect()->with('info', ...)` |
| `flash.warning` | `string\|null` | Session key `warning` | Set via `redirect()->with('warning', ...)` |

---

## Lazy Evaluation

`ziggy` and all `flash` values are wrapped in closures (`fn () => ...`). Inertia only evaluates these closures when building the response — flash values are read from the session at render time, not at middleware boot time. This ensures flash messages are correctly cleared after reading.

---

## Flash Message Convention

Controllers redirect with flash messages like this:

```php
return to_route('venues.index')->with('success', 'Venue created successfully.');
return to_route('venues.index')->with('error', 'An error occurred.');
```

In React, flash messages are typically consumed by the `useFlashToast` hook which reads `usePage().props.flash` and displays toast notifications.

---

## TypeScript Type (Inertia SharedProps)

```typescript
// resources/js/types/index.d.ts
export interface SharedProps extends InertiaSharedProps {
    name: string;
    quote: {
        message: string;
        author: string;
    };
    auth: {
        user: User | null;
    };
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    flash: {
        success: string | null;
        error: string | null;
        info: string | null;
        warning: string | null;
    };
}
```

---

## Sidebar State

`sidebarOpen` reflects the `sidebar_state` cookie:
- Cookie absent → `true` (sidebar open by default)
- Cookie `= 'true'` → `true`
- Cookie `= 'false'` → `false`

The cookie is set by the frontend when the user toggles the sidebar. Note: `sidebar_state` is in the **unencrypted cookies** list in `bootstrap/app.php`.

---

## Related

- [HandleAppearance](HandleAppearance.md) — companion middleware for appearance/theme
- [useFlashToast hook](../../Hooks/useFlashToast.md) — consumes `flash` props
- [bootstrap/app.php](../../../bootstrap/app.php) — where this middleware is registered
