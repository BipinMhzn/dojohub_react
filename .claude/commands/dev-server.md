# Dev Server

How to run dojohub_react locally.

---

## Recommended: Laravel Herd + `npm run dev`

The app is served by **Laravel Herd** at **http://dojohub_react.test** (no `php artisan serve` needed). Herd handles PHP + nginx; you only need to run Vite for the frontend.

```bash
npm run dev
```

Then visit **http://dojohub_react.test** in your browser.

---

## All-in-one: `composer dev`

For local development with queue worker + log streaming + Vite all in one terminal:

```bash
composer dev
```

This runs:
- `php artisan serve` (also at :8000, but Herd's domain is preferred)
- `php artisan queue:listen --tries=1`
- `php artisan pail --timeout=0` (live log tail)
- `npm run dev`

Concurrently with colour-coded output. Stop with `Ctrl+C`.

---

## With SSR: `composer dev:ssr`

For server-side rendering enabled (Inertia SSR):

```bash
composer dev:ssr
```

Builds the SSR bundle first, then runs:
- `php artisan serve`
- `php artisan queue:listen`
- `php artisan pail`
- `php artisan inertia:start-ssr`

Use this when you need to verify SSR-rendered HTML (SEO, share previews).

---

## URL hygiene

When sharing a URL with the user, call the Laravel Boost MCP tool:

```
get-absolute-url
```

to ensure the correct scheme/host/port for the current environment. Don't hand-type URLs that include `:8000` if Herd is serving on `:80`.

---

## Stopping

- `Ctrl+C` in the terminal — kills the concurrently process group
- Herd's nginx keeps running — that's expected; the app just won't have Vite HMR

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `dojohub_react.test` 404 in browser | Check Herd is running; ensure the project is parked in Herd |
| Frontend changes don't appear | Run `npm run dev` (Vite must be active for HMR) |
| HMR connects then disconnects | Restart `npm run dev`; clear browser cache |
| 500 error on every route | `php artisan config:clear && php artisan cache:clear` |
| Stale assets in production-like state | `npm run build` then refresh |

---

## Related

- [build.md](build.md) — Production build commands
- [database.md](database.md) — Migrations & seeding
- [test.md](test.md) — Running tests
