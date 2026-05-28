# Troubleshooting — Wiki

Common issues encountered during development and deployment, with diagnosis steps and fixes.

---

## Backend Issues

### `php artisan migrate` fails on FK constraints

**Symptom**: `SQLSTATE[HY000]: General error: 1005 Can't create table ... (errno: 150 "Foreign key constraint is incorrectly formed")`

**Cause**: Migration order issue — a FK references a table that hasn't been created yet, or the referenced column type doesn't match.

**Fix**:
```bash
php artisan migrate:fresh --seed   # drop all, re-run in order
```

If the issue is column type mismatch (e.g. FK on `users.id` which is `bigInteger` but the column is `integer`), check the migration and ensure both sides are `unsignedBigInteger` or both are `ulid/string`.

---

### Storage files not accessible (`/storage/...` returns 404)

**Symptom**: Club logos, player photos return 404.

**Cause**: Missing `public/storage` symlink.

**Fix**:
```bash
php artisan storage:link
```

Verify: `ls -la public/storage` should show a symlink to `../../storage/app/public`.

---

### Inertia returns blank white page

**Symptom**: Page loads but React doesn't mount — blank `<div id="app">`.

**Causes and fixes**:

1. **Vite assets not compiled** — run `npm run dev` (local) or `npm run build` (production).
2. **Manifest not found** — `public/build/manifest.json` is missing; rebuild with `npm run build`.
3. **SSR server crashed** — if `ssr.enabled=true`, the Node server at `http://127.0.0.1:13714` is down. Check with `curl http://127.0.0.1:13714`. Restart: `node bootstrap/ssr/ssr.js`.
4. **JS runtime error** — open browser devtools → Console for the actual error.

---

### `APP_KEY` not set error

**Symptom**: `No application encryption key has been specified.`

**Fix**:
```bash
php artisan key:generate
```

---

### Session not persisting / constantly re-logging in

**Symptom**: User is logged out on every request.

**Causes**:
- `SESSION_DRIVER=database` but `sessions` table doesn't exist → run `php artisan migrate`.
- `APP_URL` doesn't match the domain being accessed → set `APP_URL=http://dojohub_react.test` in `.env`.
- `SESSION_DOMAIN` set incorrectly.

---

### `Role or permission ... does not exist` exception (Spatie)

**Symptom**: Calling `$user->assignRole('admin')` throws.

**Cause**: Role hasn't been seeded.

**Fix**:
```bash
php artisan db:seed --class=RoleSeeder
```

Or run the full seeder:
```bash
php artisan migrate:fresh --seed
```

See [Database/Seeders.md](../Database/Seeders.md) for what each seeder creates.

---

### Audit writes failing / slowing tests

**Symptom**: Tests run slowly, or `OwenIt\Auditing` throws a write error.

**Fix**: Disable auditing in `phpunit.xml`:
```xml
<env name="AUDITING_ENABLED" value="false"/>
```

Or set `AUDITING_ENABLED=false` in `.env.testing`.

---

### Spatie permission cache stale after seeding

**Symptom**: Roles/permissions exist in DB but middleware denies access.

**Fix**:
```bash
php artisan cache:clear     # clears Spatie's permission cache
```

Spatie Permission caches the role/permission list. Any change to roles/permissions requires a cache clear.

---

### `base-system:generate` creates files in wrong location

**Symptom**: Generated module files appear in the wrong directory.

**Cause**: Command must be run from the project root.

**Fix**: Run from `dojohub_react/`:
```bash
php artisan base-system:generate MyModule --web
```

See [BaseSystem/ModuleGenerateCommand.md](../BaseSystem/ModuleGenerateCommand.md) for the full module structure that's generated.

---

## Frontend Issues

### Vite HMR not working (no hot reload)

**Symptom**: Changes to `.tsx` files don't hot-reload.

**Causes**:
- Vite dev server not running → start `npm run dev`.
- Browser extension (ad blocker) blocking Vite's WebSocket.
- Accessing via HTTPS but Vite serves HTTP → use `http://dojohub_react.test`.

---

### TypeScript errors: `Cannot find module '@/...'`

**Symptom**: TS errors on `@/` imports.

**Cause**: `tsconfig.json` path alias not resolved.

**Fix**: Verify `tsconfig.json` has:
```json
"paths": {
  "@/*": ["./resources/js/*"]
}
```

And `vite.config.ts` has:
```ts
resolve: {
  alias: { 'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy') }
}
```

The `@/` alias is registered in `tsconfig.json` only (Vite uses `laravel-vite-plugin` which handles it automatically).

---

### `route()` is not defined (SSR)

**Symptom**: SSR crashes with `ReferenceError: route is not defined`.

**Cause**: Ziggy global polyfill not installed in `ssr.tsx`.

**Fix**: Ensure `ssr.tsx` has:
```ts
setup: ({ App, props }) => {
    global.route<RouteName> = (name, params, absolute) =>
        route(name, params as any, absolute, {
            ...page.props.ziggy,
            location: new URL(page.props.ziggy.location),
        });
    return <App {...props} />;
}
```

See [Frontend/Bootstrap.md](../Frontend/Bootstrap.md).

---

### Flash toasts not showing

**Symptom**: `session()->flash('success', '...')` is set but no toast appears.

**Causes**:
1. `useFlashToast()` not called in the active layout. It's called in `AppLayout` — pages using `AuthLayout` won't get toasts.
2. The redirect response didn't include the `with()` call.
3. Flash key mismatch — `FlashMessages` interface only has `success`, `error`, `info`, `warning`.

**Fix**: Check that the controller uses `->with('success', '...')` on a redirect and that the page uses `AppLayout`.

---

### Appearance theme flashing on load (FOUT)

**Symptom**: Page briefly shows wrong theme (light flashes dark or vice versa) before stabilising.

**Cause**: `initializeTheme()` in `app.tsx` wasn't called, or the SSR output doesn't include the theme class.

**Fix**: Ensure `app.tsx` calls `initializeTheme()` at **module scope** (not inside a component):
```ts
// At bottom of app.tsx, outside createInertiaApp
initializeTheme();
```

---

### Data table pagination not updating URL

**Symptom**: Navigating to a different page doesn't update the browser URL.

**Cause**: `replace: true` in `router.get()` calls means the navigation replaces the history entry.

**Note**: This is intentional — filter changes use `replace: true` to avoid polluting browser history with every keystroke. The URL is always current (it updates), but the Back button jumps all the way back, not through intermediate filter states.

---

### File upload fails silently (no validation errors shown)

**Symptom**: Submitting a form with a file returns no errors but no file is saved.

**Common causes**:
1. **Wrong request method** — file uploads via Inertia require using `form.post()` not `form.put()` (multipart requires POST). Update forms use `POST /resource/{id}` not `PUT`.
2. **`php.ini` limits** — `upload_max_filesize` or `post_max_size` too low. Check with `php -i | grep upload_max_filesize`.
3. **Missing `storage:link`** — files saved but not accessible.

**Fix for Herd**: Edit PHP INI via Herd's UI → PHP tab → increase `upload_max_filesize`.

---

### `composer install` fails (PHP version mismatch)

**Symptom**: `Your lock file does not contain a compatible set of packages. Please run composer update.`

**Cause**: PHP version doesn't meet `^8.4` requirement.

**Fix**:
```bash
php --version   # must be 8.4+
```

With Herd, switch PHP version from the status bar menu.

---

### Tests fail with FK constraint errors (SQLite in-memory)

**Symptom**: `SQLSTATE[HY000]: General error: 19 FOREIGN KEY constraint failed`

**Cause**: SQLite enforces FKs, but test setup doesn't seed required lookup tables.

**Fix**: Seed required FKs in `setUp()`:
```php
Country::firstOrCreate(['code' => 'NP'], ['name' => 'Nepal']);
BeltRank::firstOrCreate([/* ... */]);
```

See [Tests/Index.md](../Tests/Index.md) for the FK seeding pattern.

---

### ESLint errors on `react-hooks/exhaustive-deps`

**Symptom**: `React Hook useEffect has a missing dependency: 'someValue'`

**Context**: This is a **warning**, not an error (`'react-hooks/exhaustive-deps': 'warn'` in `eslint.config.js`).

**Options**:
- Add the missing dep to the array (safe if the dep is stable).
- Use `// eslint-disable-next-line react-hooks/exhaustive-deps` if you intentionally want a mount-only effect (document the reason).

---

### `npm run types` fails

**Symptom**: TypeScript errors from `tsc --noEmit`.

**Common causes**:
1. **Module not found** — check `tsconfig.json` paths and that the file exists.
2. **Implicit `any`** — `noImplicitAny: true` is enabled; add explicit type annotations.
3. **Strict null check** — `strict: true` includes `strictNullChecks`; add null guards.

---

## Related

- [Tests/Index.md](../Tests/Index.md) — Test setup and FK seeding
- [Config/Index.md](../Config/Index.md) — Environment variables
- [Deployment.md](Deployment.md) — Local dev setup and production build
- [Frontend/Bootstrap.md](../Frontend/Bootstrap.md) — SSR setup details
