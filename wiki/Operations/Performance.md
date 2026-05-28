# Performance — Wiki

Optimization strategies, caching layers, and build configuration for DojoHub.

---

## Frontend Performance

### Code Splitting (Automatic)

Vite automatically code-splits every page in `resources/js/pages/**/*.tsx` into its own chunk via the `import.meta.glob` dynamic import in `app.tsx`:

```ts
resolve: (name) =>
    resolvePageComponent(
        `./pages/${name}.tsx`,
        import.meta.glob('./pages/**/*.tsx'),
    ),
```

On navigation, only the chunk for the next page is loaded — previous chunks are cached. The initial load only delivers the chunk for the first page.

**Result**: Large pages like Tournaments (11 sub-pages, many types) do not inflate the initial bundle.

### Inertia Partial Reloads

All data-table list pages use partial reloads to avoid re-fetching unchanged props on filter/sort/pagination changes:

```ts
router.get('/manage/venues', filters, {
    only: ['venues'],         // ← fetch only the paginated data
    preserveState: true,
    preserveScroll: true,
    replace: true,
});
```

`only: ['venues']` tells Inertia to re-run the controller but only send back the `venues` prop. Shared props (`auth`, `flash`, `ziggy`, `sidebarOpen`) are not re-fetched. This halves network payload on every filter change.

### Debounced Search

All list pages debounce the keyword search 500 ms before issuing a request:

```ts
useEffect(() => {
    const handler = setTimeout(() => setDebouncedSearch(search), 500);
    return () => clearTimeout(handler);
}, [search]);
```

Without debouncing, every keystroke would trigger a request. At 500 ms, a user typing a 5-character search word sends 1 request instead of 5.

### Server-Side Rendering (SSR)

SSR is enabled in production (`config/inertia.php`: `ssr.enabled = true`). The Node.js SSR server (`bootstrap/ssr/ssr.js`) pre-renders pages to HTML before React hydrates them.

**Benefits**:
- Faster First Contentful Paint (FCP) — browser displays content before JS executes.
- Better SEO (though DojoHub is not a public-facing app).
- Eliminates blank flash on first load.

**`initializeTheme()` prevents FOUT**: Called at module scope in `app.tsx` before React mounts, so the `dark` class is applied on `<html>` before the first paint — no flash-of-wrong-theme.

### Tailwind CSS Build

Tailwind 4 uses the `@tailwindcss/vite` plugin (not PostCSS). It scans `resources/` for class usage and purges unused styles at build time. The production CSS bundle is minimal.

---

## Backend Performance

### Config/Route/View Caching

In production, cache all Laravel caches for faster boot:

```bash
php artisan config:cache    # ~20ms faster per request
php artisan route:cache     # avoids re-parsing route files
php artisan view:cache      # pre-compiles Blade views (minimal in Inertia)
php artisan event:cache     # pre-discovers event listeners
```

**Clear all caches**:
```bash
php artisan optimize:clear
```

**Re-cache after deployment**:
```bash
php artisan optimize
```

`php artisan optimize` is a shortcut that runs config, route, view, and event caching in one command.

---

### Repository `getList()` — N+1 Prevention

The `onBeforeResult(Builder $builder)` hook in each repository is specifically for eager loading. Override it to prevent N+1 queries on list pages:

```php
// app/Modules/Players/Repositories/PlayerRepository.php
protected function onBeforeResult(Builder $builder): Builder
{
    return $builder->with(['country', 'currentBeltRank']);
}
```

Without this, a list of 20 players would execute 41 queries (1 for list + 20 for `country` + 20 for `currentBeltRank`). With eager loading: 3 queries total.

**Key rule**: `onBeforeResult` only applies to `getList()`. `getById()` must also override eager loading separately:

```php
public function getById(string $id): ?Model
{
    return $this->model
        ->with(['country', 'currentBeltRank'])
        ->findOrFail($id);
}
```

---

### Pagination Defaults

`BaseModel::$perPage = 10`. All list queries are paginated — no unbounded `->get()` on large tables. Default is 10 rows; user can change to 25/50 via the per-page selector.

For the tournament module, `categories` relationships are loaded with a count rather than the full set:

```php
->withCount('registrations')
```

This avoids hydrating full registration objects when only the count is needed for the categories list.

---

### Auditing Performance

Laravel Auditing (`owen-it/laravel-auditing`) writes to the `audits` table on every create/update/delete of any `AppBaseModel`. In high-write scenarios:

- Disable in tests: `AUDITING_ENABLED=false` in phpunit.xml
- Consider using the `AuditQueue` driver for deferred writes
- Monitor `audits` table size — it grows quickly; purge old records periodically

```php
// Purge audits older than 90 days (run as scheduled job)
\OwenIt\Auditing\Models\Audit::where('created_at', '<', now()->subDays(90))->delete();
```

---

### File Upload Optimization

Club logos and player photos go through the `FileUploadTrait`. Images are stored as-uploaded with no server-side resizing. For large profile photos (e.g. 5 MB JPEG), this can impact page load if `<img>` tags load the full-size image.

**Recommendation for production**: Add a resize step in the upload handler or use a CDN with on-the-fly resizing (e.g. Cloudflare Images, AWS CloudFront + Lambda@Edge).

Current constraint: max upload size is 5 MB (enforced client-side by `FileUploader`; PHP must allow at least this with `upload_max_filesize` and `post_max_size`).

---

### Queue — Database Driver

The default `QUEUE_CONNECTION=database` stores jobs in the MySQL `jobs` table. For high-throughput scenarios, switch to Redis:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

DojoHub has no heavy background jobs currently — the database driver is adequate.

---

## Monitoring

### Health Check Endpoint

`bootstrap/app.php` registers:

```php
->withRouting(
    health: '/up',
)
```

`GET /up` returns HTTP 200 with no body when the app is healthy. Use this for load balancer health checks or uptime monitors (Uptime Robot, etc.).

### Laravel Telescope (Local)

Check `config/telescope.php` — if Telescope is installed, `APP_ENV=local` enables the debug UI at `/telescope`. Useful for:
- Inspecting Inertia requests (see the full JSON payload)
- Viewing executed queries (spot N+1 issues)
- Checking job dispatches

Disable in production: `TELESCOPE_ENABLED=false`.

### Log Channel

```env
LOG_CHANNEL=stack    # writes to storage/logs/laravel.log
LOG_LEVEL=debug      # verbose in dev; set to 'error' in production
```

---

## Build Size Analysis

To inspect the production bundle size:

```bash
# Build with stats
VITE_ANALYZE=true npm run build    # requires vite-bundle-visualizer if installed

# Otherwise, check output sizes manually
npm run build 2>&1 | grep -E '\.(js|css)' | sort -k2 -n
```

Largest expected chunks:
- `resources/js/pages/tournaments/matches/index.tsx` — bracket rendering, many types
- `resources/js/pages/tournaments/index.tsx` — all tournament types/enums
- `components/ui/sidebar.tsx` — complex shadcn component (Radix + context)

---

## Related

- [Deployment.md](Deployment.md) — Build commands and production setup
- [BaseSystem/BaseRepository.md](../BaseSystem/BaseRepository.md) — `onBeforeResult` hook for eager loading
- [Tests/Index.md](../Tests/Index.md) — Performance configuration in tests
