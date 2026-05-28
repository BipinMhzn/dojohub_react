# Tests

How to run the test suite for dojohub_react.

---

## Standard runs

```bash
composer test                      # Full suite (clears config first)
php artisan test                   # Same, no config clear
```

`composer test` is what CI runs and what you should run before committing.

---

## Filter to one class or method

```bash
php artisan test --filter=VenueTest
php artisan test --filter=test_authenticated_user_can_view_venues_list
```

The filter matches against class names AND method names. Partial matches work:

```bash
php artisan test --filter=Venue                       # Any test with "Venue" in name
php artisan test --filter="test_guests_are_redirected" # Any "redirected" guest test
```

---

## Coverage report

```bash
php artisan test --coverage
```

Slow (10–30x slower than normal). Requires `xdebug` or `pcov`. Use in CI or before a release, not in the inner loop.

For per-class coverage:

```bash
php artisan test --coverage --min=80                  # Fail if below 80%
```

---

## Parallel tests

```bash
php artisan test --parallel
```

Spawns one PHPUnit process per CPU core. Uses separate SQLite in-memory DBs. Faster for large suites, but harder to debug — disable for failing tests.

---

## Stop on first failure

```bash
php artisan test --stop-on-failure
php artisan test --stop-on-error
```

Useful when running locally and you want immediate feedback. Don't use in CI — you want the full picture.

---

## Watch mode (manual)

PHPUnit doesn't have native watch mode. For TDD, use a file watcher like `entr`:

```bash
find app tests | entr -c php artisan test --filter=VenueTest
```

Or trigger via your editor's "run tests on save" extension.

---

## Where to look

| Folder | Purpose |
|--------|---------|
| `tests/Feature/` | HTTP-level tests (most tests live here) |
| `tests/Unit/` | Pure logic tests (rare; no DB) |
| `tests/TestCase.php` | Base class with `CreatesApplication` |

---

## DB isolation

Every test using `RefreshDatabase` trait migrates a fresh in-memory SQLite DB and rolls back at teardown. No cross-test pollution.

```php
class VenueTest extends TestCase
{
    use RefreshDatabase;
    // ...
}
```

---

## Common patterns

| Want | Command |
|------|---------|
| Run everything | `composer test` |
| Run one file | `php artisan test --filter=VenueTest` |
| Run one method | `php artisan test --filter=test_specific_method_name` |
| Stop on first failure | `php artisan test --stop-on-failure` |
| Coverage | `php artisan test --coverage` |
| Parallel | `php artisan test --parallel` |

---

## Pitfalls

- Don't write tinker/verification scripts to "prove" something works — write a test. Tests stick around; tinker output doesn't.
- Don't use `dd()` in a test — it crashes the test silently. Use `ray()` or `Log::info()`.
- Don't mock the repository in a Feature test — exercise the real stack.
- Time-sensitive code: freeze time with `$this->travel(...)` or `Carbon::setTestNow()`.

---

## Related

- [.claude/guidelines/testing-patterns.md](../guidelines/testing-patterns.md) — Test writing rules and templates
- `tests/Feature/VenueTest.php` — Reference test class
