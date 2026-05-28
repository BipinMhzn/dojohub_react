# Code Generator

The local `base_system` package ships with an Artisan command that scaffolds a new module skeleton.

---

## Scaffold a module

```bash
# Inertia (web) module — pages return Inertia::render
php artisan base-system:generate Tournament --web

# JSON API module — returns JSON
php artisan base-system:generate Tournament
```

The generator creates:

```
app/Modules/{Plural}/
├── Controllers/{Singular}Controller.php
├── Filters/{Singular}Filter.php
├── Interfaces/{Singular}Interface.php
├── Models/{Singular}.php
├── Repositories/{Singular}Repository.php
├── Requests/{Singular}CreateRequest.php
├── Requests/{Singular}UpdateRequest.php
├── Requests/{Singular}ListRequest.php
├── Routes/
│   ├── api.php
│   └── web.php
└── ServiceProvider.php
```

And the matching System model:

```
app/System/{Singular}/Models/{Singular}.php
```

---

## What it does NOT do

- ❌ Creates a migration (write it by hand — use `php artisan make:migration`)
- ❌ Registers the module in `config/app.php` (auto-discovered by `BaseSystemProvider`)
- ❌ Writes tests (do those manually — see [testing-patterns.md](../guidelines/testing-patterns.md))
- ❌ Creates frontend pages (build `resources/js/pages/{module}/*.tsx` by hand)
- ❌ Adds routes to a parent route file (your `Routes/web.php` is the source of truth)

---

## After scaffolding

1. **Write the migration** — `php artisan make:migration create_{module}s_table`
   - Use `$table->ulid('id')->primary()`
   - Add `$this->editorLogs($table)` (with `use \Database\Traits\EditorTrait;`)
   - Add FKs with `onDelete('restrict')`
2. **Write a factory** in `database/factories/{Singular}Factory.php`
3. **Wire the System model's `newFactory()`** to return your factory
4. **Add `$fillable` + `$sortableColumns` + casts** to the System model
5. **Edit the Repository** — typically you add `onBeforeResult()` for eager loading and override `getById()`
6. **Edit the Filter** — public methods become query-string filters
7. **Edit the Requests** — fill in `rules()` (see [forms-and-validation.md](../guidelines/forms-and-validation.md))
8. **Build frontend pages** under `resources/js/pages/{module}/`
9. **Write tests** in `tests/Feature/{Module}Test.php`

---

## Convention reminder

- Module name is **Singular** in the CLI (`Tournament`, not `Tournaments`)
- Generator pluralises the directory (`Tournaments/`)
- Existing reference modules: `Venues`, `Clubs`, `Players`, `Tournaments`, `Users`, `BeltRanks`, `Coaches`, `Referees`, `Countries`, `TournamentCategories`, `TournamentRuleSets`

---

## Modify the stubs

Templates live in `base_system/src/Stubs/`. Edit them to change the boilerplate, then regenerate.

```bash
ls base_system/src/Stubs/
```

If you find yourself manually editing every generated file the same way, the stub is wrong — fix the stub.

---

## Related

- [.claude/guidelines/architecture.md](../guidelines/architecture.md) — Module pattern
- [base_system/README.md](../../base_system/README.md) — Base classes
- [database.md](database.md) — Migration commands
- [test.md](test.md) — Test runner
