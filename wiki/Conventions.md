# Conventions

Project-wide rules that aren't enforced by tooling. Follow them when adding new code.

---

## Primary keys

- All domain tables use **ULID** primary keys (`HasUlids`).
- Only `users` keeps Laravel's default auto-increment integer PK.

```php
Schema::create('venues', function (Blueprint $table) {
    $table->ulid('id')->primary();
    // ...
});
```

---

## Audit columns

Every domain table includes:

```php
$table->string('created_by')->nullable();
$table->string('updated_by')->nullable();
$table->string('deleted_by')->nullable();
$table->timestamps();
$table->softDeletes();
```

Population is automatic via the `EditorLogs` trait (bundled in `AppBaseModel`).

---

## Soft delete + FK RESTRICT

A pattern used across `addresses` ↔ `venues`/`clubs`, `players` ↔ `clubs_players`, `tournament_categories` ↔ `tournament_registrations`, etc.

| Constraint | Behaviour |
|------------|-----------|
| `ON DELETE RESTRICT` (DB) | Blocks **hard** DELETE only |
| `SoftDeletes` (Eloquent) | Issues an UPDATE that sets `deleted_at` |

Because Eloquent soft-delete is an UPDATE, the RESTRICT FK is **not** triggered. You can safely soft-delete the parent first, then the child — or vice versa. Pattern used in repositories:

```php
$venue->address?->delete();   // soft-delete address (UPDATE)
$venue->delete();             // soft-delete venue (UPDATE)
```

Never use `forceDelete()` in module code unless you've audited every FK referencing the row.

---

## Routes

### Web modules (Inertia)

All web routes share the same shape:

```php
Route::group([
    'middleware' => ['auth', 'verified'],
    'prefix'     => 'manage',
], function () {
    // ...
});
```

Resource routes use `Route::resource(...)->except('show')` unless the module needs a detail page (Clubs does, for the membership list).

### PUT vs POST for update

| Module | Update method | Why |
|--------|---------------|-----|
| Venues, Tournaments → Categories | `PUT` | No file upload |
| Clubs, Players, Tournaments | `POST` | Multipart file uploads (HTML forms only allow POST) |

When using POST-for-update, register an extra explicit route:

```php
Route::resource('clubs', ClubsController::class)->except(['update'])->names('clubs');
Route::post('clubs/{club}', [ClubsController::class, 'update'])->name('clubs.update');
```

Inertia's `router.put()` on the frontend handles this transparently via `_method` field.

### Route naming

- Dot notation: `tournaments.categories.registrations.index`
- One name per route — always use `route('name')` in templates and controllers, never hard-code paths.

### Nested resources

Tournaments use **deep** nesting:

```
tournaments.index                                  → GET    /manage/tournaments
tournaments.categories.index                       → GET    /manage/tournaments/{tournament}/categories
tournaments.categories.registrations.index         → GET    /manage/tournaments/{tournament}/categories/{category}/registrations
tournaments.categories.matches.index               → GET    /manage/tournaments/{tournament}/categories/{category}/matches
tournaments.categories.matches.generate            → POST   /manage/tournaments/{tournament}/categories/{category}/matches/generate
tournaments.categories.matches.update-result       → PATCH  /manage/tournaments/{tournament}/categories/{category}/matches/{match}/result
```

Hyphenated action names use `update-result`, `update-schedule` (not snake_case).

---

## File naming

| Layer | Convention | Example |
|-------|------------|---------|
| PHP classes | StudlyCase, one class per file | `TournamentMatchCompetitor.php` |
| Migrations | snake_case, timestamped | `2025_12_24_072131_create_tournament_matches_table.php` |
| Blade / Inertia page entry | (n/a — pages are React) | — |
| React pages and components | kebab-case files, PascalCase exports | `pages/tournaments/categories/index.tsx`, `partials/tournament-header.tsx` |
| TypeScript types | kebab-case files; PascalCase types | `types/index.d.ts` |
| Routes | dot notation | `tournaments.categories.matches.index` |

> **There is no `snake_case` rule for PHP files.** PHP source files mirror their StudlyCase class name. Only migration filenames are snake_case (Laravel convention).

---

## Validation

- Validation always lives in Form Request classes (`Requests/{Module}{Action}Request.php`).
- Never inline `$request->validate(...)` inside a controller.
- Update requests extend Create requests (`class VenueUpdateRequest extends VenueCreateRequest`) unless rules differ.
- All web requests extend `BaseWebRequest` / `BaseWebListRequest`.

Existing modules use **array-style** rules:

```php
'name' => ['required', 'string', 'max:250'],
```

Stick with the array form when adding new Requests — it allows interleaving custom rule objects.

---

## Eloquent rules

- Always use Eloquent relationship methods (with return type hints), not manual joins.
- Prefer `Model::query()` over `DB::table(...)`.
- Eager-load anything you'll render. The `onBeforeResult` hook is the standard place — see [Architecture.md](Architecture.md#optional-hooks).
- Casts go in a `casts()` method (Laravel 12 form), not the `$casts` property.

```php
protected function casts(): array
{
    return [
        'gender' => PlayerGenderEnum::class,
        'dob'    => 'date',
    ];
}
```

---

## Enums

- Stored in `app/System/{Entity}/Foundation/Enums/{Name}Enum.php`.
- Backed enums only — int-backed for status/category fields.
- Cases are TitleCase (`Pending`, `InProgress`, `Cancelled`) — no SNAKE_CASE.
- Stored as `tinyint unsigned` in DB unless the value space needs more.

See [Enums.md](Enums.md) for the canonical list.

---

## Code style

- **Backend:** Laravel Pint. After every PHP change: `vendor/bin/pint --dirty`. Never run `--test`.
- **Frontend:** Prettier + Tailwind class sorter — `npm run format`. ESLint via `npm run lint`.
- **TypeScript:** strict mode; no `any`. Verify with `npm run types`.
- Curly braces are mandatory on all control structures, even single-line.
- Always declare return types and parameter types on PHP methods.
- PHPDoc only when the types alone aren't enough (e.g. array shape).

---

## Testing

- Feature tests live in `tests/Feature/`. Unit tests in `tests/Unit/`.
- Every change is tested. Run with `php artisan test --filter={Name}`.
- Use factories (with custom states) over hand-built models.
- Existing references: `tests/Feature/VenueTest.php`, `tests/Feature/ClubPlayerTest.php`.

---

## Common pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| Putting validation in a controller | Always use a Form Request |
| Eager-loading in `onBeforeResult` only, then `getById` misses the relation | Override `getById` too |
| Forgetting `unset($validated['logo'])` after handling a file upload | Strip uploaded `UploadedFile` from the array before passing to the repository |
| Adding a System model relation but referencing the wrong namespace from the module | The module model should `use App\Modules\{Plural}\Models\X`, not `App\System\...` |
| Using `Route::resource()` with `update` and POST-for-update at the same time | Use `->except('update')` then register the POST route explicitly |
| Hard-deleting before checking soft-delete sequence | See [Soft delete + FK RESTRICT](#soft-delete--fk-restrict) |
