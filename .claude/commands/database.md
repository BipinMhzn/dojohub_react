# Database

Migration, seeding, and DB workflow commands.

---

## Apply migrations

```bash
php artisan migrate
```

Applies any pending migrations to the configured DB (MySQL in dev/prod, SQLite in tests).

---

## Reset DB

```bash
php artisan migrate:fresh           # Drop all tables, run all migrations
php artisan migrate:fresh --seed    # Plus run seeders
```

**`migrate:fresh` destroys all data.** Use only when:
- Setting up the project for the first time
- After pulling a branch with a destructive migration
- In a feature branch you control

---

## Roll back

```bash
php artisan migrate:rollback                # Roll back the last batch
php artisan migrate:rollback --step=3       # Roll back 3 migrations
php artisan migrate:reset                   # Roll back ALL migrations
```

Use these for fixing a bad migration locally. **Don't** run on a shared dev DB without warning others.

---

## Create a migration

```bash
php artisan make:migration create_venues_table              # Create table
php artisan make:migration add_status_to_venues_table       # Alter table
```

Filename auto-prefixed with timestamp. Edit the file in `database/migrations/`. See [database-conventions.md](../guidelines/database-conventions.md) for column conventions (ULID, `editorLogs()`, FK RESTRICT).

---

## Seeders

```bash
php artisan db:seed                              # Run DatabaseSeeder
php artisan db:seed --class=CountrySeeder        # Run one seeder
```

Seeders live in `database/seeders/`. `DatabaseSeeder.php` is the entry point that delegates to module-specific seeders.

---

## Inspect schema

Use the Laravel Boost MCP tool when investigating live schema:

```
database-schema       # All tables + columns
database-query        # Read-only SQL (SELECT)
```

Or via Tinker:

```bash
php artisan tinker
>>> Schema::hasTable('venues');
>>> Schema::getColumnListing('venues');
```

Don't run raw `DESCRIBE` SQL — use Tinker or Boost.

---

## Inspect data (read-only)

```bash
php artisan tinker
>>> App\Modules\Venues\Models\Venue::count();
>>> App\Modules\Venues\Models\Venue::with('address.country')->first();
```

Or use Boost's `database-query` for read-only SQL.

---

## Status

```bash
php artisan migrate:status         # Which migrations have/haven't run
```

---

## Common workflows

| When | Commands |
|------|----------|
| First setup | `php artisan migrate:fresh --seed` |
| After pulling | `php artisan migrate` |
| Adding a new table | `make:migration` → edit → `migrate` → write factory + tests |
| Schema is corrupt locally | `migrate:fresh --seed` |
| Adding a column to existing table | `make:migration add_x_to_y_table` (NOT a new `create_*` migration) |

---

## Pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| Editing an old migration that's already run | Create a new `add_x_to_y_table` migration; never mutate history |
| `migrate:fresh` on a shared dev DB | Use a personal local DB; `migrate:fresh` wipes everything |
| Forgetting `editorLogs($table)` in a new migration | See [database-conventions.md](../guidelines/database-conventions.md#editorlogs-trait--audit-columns) |
| Not using ULID for PK on a domain table | All domain tables use `$table->ulid('id')->primary()` |
| Cascade delete by accident | Default to `onDelete('restrict')`; cascade only for true sub-entities |

---

## Related

- [.claude/guidelines/database-conventions.md](../guidelines/database-conventions.md) — Migration & schema rules
- [wiki/Database.md](../../wiki/Database.md) — Authoritative schema reference
- [wiki/Enums.md](../../wiki/Enums.md) — All enum values
