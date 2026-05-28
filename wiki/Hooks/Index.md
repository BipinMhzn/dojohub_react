# Custom Hooks

All hooks live under `resources/js/hooks/`. There are 5 hooks in the codebase. Each is a focused, single-responsibility utility.

---

## `useAppearance` — Dark/Light/System Theme

**File:** `resources/js/hooks/use-appearance.tsx`

### Purpose

Manages the application's colour scheme preference. Handles persistence to `localStorage` and a cookie, applies the `dark` class to `<html>`, and reacts to OS-level `prefers-color-scheme` changes. Also exports `initializeTheme()` — a side-effect function called at module level in `app.tsx` before React mounts.

### Type

```ts
export type Appearance = 'light' | 'dark' | 'system';
```

### `initializeTheme()` — standalone function

```ts
export function initializeTheme(): void
```

- **Not a hook.** Called synchronously at module scope in `app.tsx` so the theme is applied before the first React render (prevents flash-of-wrong-theme).
- Reads `localStorage.appearance` (cast to `Appearance`) or defaults to `'system'`.
- Calls `applyTheme(savedAppearance)` to add/remove the `dark` class on `<html>`.
- Registers a `change` listener on `window.matchMedia('(prefers-color-scheme: dark)')` so OS theme switches are applied immediately.

### `useAppearance()` hook

```ts
export function useAppearance(): {
    appearance: Appearance;
    updateAppearance: (mode: Appearance) => void;
}
```

**How it works:**

1. Initialises local state to `'system'` (will be overwritten on mount).
2. `updateAppearance(mode)` — memoised with `useCallback`:
   - Sets React state.
   - Persists to `localStorage.appearance`.
   - Sets a `appearance` cookie (used by the server for SSR-aware theme).
   - Calls `applyTheme(mode)` to update the `<html>` class immediately.
3. `useEffect` on mount:
   - Reads `localStorage.appearance`.
   - Calls `updateAppearance` with the saved value (or `'system'`).
   - Returns a cleanup that removes the `prefers-color-scheme` listener.

### Where it's used

- `app-layout.tsx` — reads `appearance` to pass as the `theme` prop to `<Toaster>` so sonner's toast styling matches the active theme.
- `pages/settings/appearance.tsx` — exposes `updateAppearance` to the user via the appearance settings page.
- `components/appearance-tabs.tsx`, `components/appearance-dropdown.tsx` — UI controls that call `updateAppearance`.

### Edge cases

- **SSR safety:** `initializeTheme` is called only in `app.tsx` (CSR). `useAppearance` reads `localStorage` inside `useEffect`, which never runs during SSR. The `mediaQuery()` helper should guard against `window` being undefined — this is safe because effects don't run in Node.
- **Cookie vs localStorage:** The cookie allows the Laravel backend to read the preference and potentially set the correct initial class server-side, avoiding FOWT even on the very first load.

---

## `useFlashToast` — Inertia Flash → sonner Toasts

**File:** `resources/js/hooks/use-flash-toast.ts`

### Purpose

Bridges Laravel's Inertia flash messages to sonner toast notifications. After any Inertia navigation, if the server set a flash message, the appropriate toast appears automatically.

### Signature

```ts
export function useFlashToast(): void
```

Returns nothing. Side-effect only.

### How it works

```ts
const { flash } = usePage<SharedData>().props;

useEffect(() => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error)   toast.error(flash.error);
    if (flash?.info)    toast.info(flash.info);
    if (flash?.warning) toast.warning(flash.warning);
}, [flash?.success, flash?.error, flash?.info, flash?.warning]);
```

1. Reads `flash` from the Inertia shared props (`SharedData.flash`, type `FlashMessages`).
2. Effect dependencies are the four individual flash strings. The effect fires whenever any of them changes — i.e. on every Inertia navigation that carries a flash payload.
3. Calls the appropriate `toast.*` method from `sonner`. Each method shows a differently styled notification (green for success, red for error, etc.).
4. Null / undefined flash values are skipped — no toast is shown for absent messages.

### Where it's used

Called once in `app-layout.tsx`, which wraps all authenticated pages. Because it is called at the layout level, every page that uses `AppLayout` automatically gets flash toasts without needing to call the hook itself.

### Flash message structure

The `FlashMessages` interface (from `types/index.d.ts`):

```ts
interface FlashMessages {
    success: string | null;
    error:   string | null;
    info:    string | null;
    warning: string | null;
}
```

The Laravel backend sets these via `session()->flash('success', 'Record saved.')` or the `with()` redirect helper.

---

## `useInitials` — Avatar Initials from Full Name

**File:** `resources/js/hooks/use-initials.tsx`

### Purpose

Derives a 1–2 character initials string from a person's full name. Used wherever an avatar image is absent.

### Signature

```ts
export function useInitials(): (fullName: string) => string
```

Returns a stable memoised callback.

### How it works

```ts
return useCallback((fullName: string): string => {
    const names = fullName.trim().split(' ');
    if (names.length === 0) return '';
    if (names.length === 1) return names[0].charAt(0).toUpperCase();
    return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
}, []);
```

1. Trims whitespace and splits on spaces to get name parts.
2. Empty string → returns `''`.
3. Single name token → returns first character uppercased (e.g. `'Ronaldo'` → `'R'`).
4. Multiple tokens → first char of first name + first char of last name, both uppercased (e.g. `'John Doe'` → `'JD'`, `'Maria Jose Santos'` → `'MS'`).
5. Wrapped in `useCallback` with an empty dependency array — the function reference is stable across renders.

### Where it's used

- `components/nav-user.tsx` — user avatar fallback in the sidebar navigation.
- `components/user-info.tsx` — user info display component.
- `components/user-menu-content.tsx` — dropdown menu header.

### Edge cases

- Extra spaces between words: `trim()` handles leading/trailing, but `split(' ')` will produce empty string elements for internal double spaces. This would affect the count but not produce a crash — the initials may just use an unexpected token. In practice, user names from the database are normalised.
- Single-character names produce single-character initials.

---

## `useIsMobile` — Reactive Mobile Breakpoint

**File:** `resources/js/hooks/use-mobile.tsx`

### Purpose

Returns a boolean that is `true` when the viewport is narrower than 768 px. Updates reactively as the window is resized.

### Signature

```ts
export function useIsMobile(): boolean
```

### Constants

```ts
const MOBILE_BREAKPOINT = 768; // px
```

### How it works

```ts
const [isMobile, setIsMobile] = useState<boolean>();

useEffect(() => {
    const mql = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`);
    const onChange = () => { setIsMobile(window.innerWidth < MOBILE_BREAKPOINT); };
    mql.addEventListener('change', onChange);
    setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
    return () => mql.removeEventListener('change', onChange);
}, []);

return !!isMobile;
```

1. State initialises as `undefined` (avoids a server-side assertion on `window`).
2. On mount, creates a `MediaQueryList` for `(max-width: 767px)` and attaches a `change` listener.
3. Sets the initial value synchronously from `window.innerWidth`.
4. Cleanup removes the listener on unmount.
5. Returns `!!isMobile` — converts `undefined` → `false` on the initial SSR render.

### Where it's used

- `components/ui/sidebar.tsx` — drives whether the sidebar is in sheet (mobile overlay) or fixed mode.
- Any component that needs to conditionally render or adjust layout for mobile.

### Edge cases

- **SSR safety:** State is `undefined` until `useEffect` runs (never on the server). `!!undefined` returns `false`, so SSR renders the desktop layout. The browser will correct to the true value after hydration.
- The breakpoint matches Tailwind's `md` breakpoint (768 px).

---

## `useMobileNavigation` — Mobile Navigation Cleanup

**File:** `resources/js/hooks/use-mobile-navigation.ts`

### Purpose

Returns a callback that removes the `pointer-events: none` inline style from `document.body`. This style is set by Radix UI's Sheet (the mobile sidebar overlay) when it opens, to prevent background interaction. When the user navigates away via a link inside the sheet, the sheet unmounts but Radix may not always clean up `pointer-events`, leaving the page unclickable. This hook provides a cleanup handler to call on navigation.

### Signature

```ts
export function useMobileNavigation(): () => void
```

Returns a stable memoised callback.

### How it works

```ts
return useCallback(() => {
    document.body.style.removeProperty('pointer-events');
}, []);
```

1. Wrapped in `useCallback` with an empty dependency array — stable reference.
2. When called, removes the `pointer-events` property from `document.body`'s inline style object. Does nothing if the property is not present (safe to call unconditionally).

### Where it's used

- `components/app-sidebar.tsx` — navigation links inside the mobile sidebar sheet call this callback as their `onClick` handler to ensure the page is interactive after navigation.

### Edge cases

- **SSR safety:** `document` is only accessed inside the callback, which is never called during server rendering. The `useCallback` itself has no side effects.
- Calling the callback on desktop (where `pointer-events` was never set) is a no-op.

---

## Related

- [Frontend Overview](../Frontend/Index.md)
- [Bootstrap — initializeTheme usage](../Frontend/Bootstrap.md)
- [Types — SharedData / FlashMessages](../Types/Index.md)
- [Layouts — AppLayout (useFlashToast + useAppearance)](../Layouts/Index.md)
