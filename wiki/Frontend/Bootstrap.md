# Frontend Bootstrap

This document covers the two entry point files that initialise the React application: `resources/js/app.tsx` (client-side rendering and SSR hydration) and `resources/js/ssr.tsx` (pure server-side rendering in Node.js).

---

## `app.tsx` — CSR and Hydration Entry

**File:** `resources/js/app.tsx`

```tsx
import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: { color: '#4B5563' },
});

initializeTheme();
```

### CSR Bootstrap Flow

1. **CSS import** — `app.css` (Tailwind 4 directives) is imported first so styles are available before any paint.
2. **`appName` constant** — reads `VITE_APP_NAME` from Vite's env object. Falls back to `'Laravel'` if the variable is absent. This value is inlined at build time.
3. **`createInertiaApp`** — registers Inertia with three key options:
   - `title` — wraps every page's `<title>` as `"${title} - ${appName}"`. Pages pass their own segment via `usePage().props.title` or the Inertia `title` helper.
   - `resolve` — uses `resolvePageComponent` with a Vite glob of every `.tsx` file under `pages/`. Vite code-splits each page into a separate chunk; `resolvePageComponent` does a lazy import of the matching chunk on navigation.
   - `setup` — receives the mount element (`el`), the root `App` component, and initial Inertia props. Creates a React 19 concurrent root with `createRoot` and calls `root.render`.
4. **`progress`** — Inertia's built-in navigation progress bar, coloured `#4B5563` (Tailwind's `neutral-600`).
5. **`initializeTheme()`** — called at **module scope**, outside `createInertiaApp`. This means it runs synchronously before React's first paint, so the correct light/dark class is on `<html>` before any component renders. This eliminates flash-of-wrong-theme (FOWT).

### Why `initializeTheme()` is outside the React tree

If theme initialisation were deferred until a `useEffect` inside a component, there would be a frame — potentially visible — where the wrong theme class is applied. By running at module level, the class is set as part of the same JavaScript evaluation that registers Inertia, before `root.render` is called.

---

## `ssr.tsx` — SSR Entry

**File:** `resources/js/ssr.tsx`

```tsx
import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { type RouteName, route } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx'),
            ),
        setup: ({ App, props }) => {
            global.route<RouteName> = (name, params, absolute) =>
                route(name, params as any, absolute, {
                    ...page.props.ziggy,
                    location: new URL(page.props.ziggy.location),
                });
            return <App {...props} />;
        },
    }),
);
```

### SSR Bootstrap Flow

1. **`createServer`** — starts an Inertia SSR server (an HTTP server that Laravel's Inertia package calls internally for each page request). It receives a callback that accepts a `page` object describing the current Inertia page.
2. **`createInertiaApp` (SSR mode)** — configured with `page` (the current request's page data) and `render: ReactDOMServer.renderToString`. Instead of mounting to a DOM element, it calls `renderToString` on the resolved page component and returns the resulting HTML to the Laravel backend.
3. **`title`** — same format as CSR: `"${title} - ${appName}"`.
4. **`resolve`** — same glob as CSR; Vite builds separate SSR bundles for each page.
5. **`setup`** — no DOM element is available in Node. The callback:
   - Installs the Ziggy `route()` global polyfill (see below).
   - Returns `<App {...props} />` — this JSX is what `renderToString` processes.

### Ziggy Global Polyfill in SSR

In the browser, `route()` is available as a global constant declared in `types/global.d.ts`. In Node.js there is no `window` or DOM, so Ziggy must be initialised from the page props that Laravel sends with each SSR request.

The polyfill installs the function on `global` (the Node.js equivalent of `window`):

```ts
global.route<RouteName> = (name, params, absolute) =>
    route(name, params as any, absolute, {
        ...page.props.ziggy,          // route list from the server
        location: new URL(page.props.ziggy.location),  // parsed URL object
    });
```

Every `route()` call inside a page component works identically in SSR and CSR because both environments expose the same function signature on the same global name. The `page.props.ziggy` payload is injected by the Laravel `Ziggy` facade and contains the full route list for the current request.

---

## Environment Variable: `VITE_APP_NAME`

Set in `.env`:

```
VITE_APP_NAME="DojoHub"
```

Vite replaces `import.meta.env.VITE_APP_NAME` with the literal string at build time. Only variables prefixed `VITE_` are exposed to the frontend bundle. The fallback `'Laravel'` is a safety net for environments where the variable is not set.

The value appears in every browser tab title:

```
Dashboard - DojoHub
Tournaments - DojoHub
```

---

## Progress Bar

Inertia's navigation progress bar is enabled with:

```ts
progress: { color: '#4B5563' },
```

`#4B5563` is Tailwind's `neutral-600`. The bar appears at the top of the viewport during Inertia navigations that take longer than ~250 ms. It is a thin horizontal stripe, not a full-page loading overlay.

---

## Related

- [Frontend Overview](Index.md)
- [Hooks — useAppearance / initializeTheme](../Hooks/Index.md)
- [Types — SharedData / Ziggy](../Types/Index.md)
