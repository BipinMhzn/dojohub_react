# Error Handling Guideline

How to raise, catch, and surface errors in dojohub_react.

---

## Three layers of error handling

```
┌─────────────────────────────────────────────┐
│ 1. Framework / Validation                   │
│    ValidationException, ModelNotFoundException
│    → Laravel auto-handles, Inertia surfaces  │
├─────────────────────────────────────────────┤
│ 2. Module domain exceptions                 │
│    VenueInUseException, PlayerAlreadyRegistered
│    → Caught in controller, flash error      │
├─────────────────────────────────────────────┤
│ 3. Unexpected (catch-all)                   │
│    Generic Exception                        │
│    → Logged, 500 page, audit log            │
└─────────────────────────────────────────────┘
```

---

## Module domain exceptions

Each module has an `Exceptions/` folder. Exceptions are **final**, extend `Exception`, and live in the module namespace:

```php
// app/Modules/Venues/Exceptions/VenueInUseException.php
namespace App\Modules\Venues\Exceptions;

use Exception;

final class VenueInUseException extends Exception {}
```

**Rules:**
- `final` keyword — exceptions aren't extended
- One file per exception
- Naming: `{Subject}{State}Exception` (e.g. `VenueInUseException`, `VenueMatInUseException`, `PlayerAlreadyRegisteredException`)
- The message is set when thrown — no hard-coded constructor message

```php
// Throwing
throw new VenueInUseException('Venue cannot be deleted; it has scheduled tournaments.');
```

---

## Catching in controllers — flash & redirect

For domain exceptions that the user can recover from (typically conflict/state errors), catch in the controller and redirect back with a flash error:

```php
public function destroy(string $venue): RedirectResponse
{
    try {
        $this->repository->deleteVenue($venue);

        return to_route('venues.index')->with('success', 'Venue deleted successfully.');
    } catch (VenueInUseException $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

**Pattern:** repository throws → controller catches → user sees a toast with the message. The frontend reads `flash.error` from Inertia shared props and displays it via the `useFlashToast` hook.

---

## When to throw a domain exception

Throw a domain exception in the **repository** when:

- A precondition fails that the controller couldn't have predicted (e.g. FK constraint about to fire)
- A business rule is violated (e.g. registering the same player twice)
- A state machine transition is invalid (e.g. completing an already-cancelled tournament)

**Don't throw** for input validation — that's the Form Request's job.

```php
// ✅ In a repository
public function deleteVenue(string $venueId): true
{
    $venue = $this->model->newQuery()->findOrFail($venueId);

    if ($venue->mats()->whereHas('tournamentMatches')->exists()) {
        throw new VenueInUseException('Venue has scheduled matches and cannot be deleted.');
    }

    $venue->address?->delete();
    $venue->delete();

    return true;
}
```

---

## Validation errors (ValidationException)

Form Requests handle these automatically. Inertia surfaces them on the page via `errors` from `useForm()`:

```tsx
const { data, setData, post, errors } = useForm({...});

<Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
<InputError message={errors.name} />
```

**Don't** call `$request->validate(...)` in a controller — always use a Form Request (`VenueCreateRequest`, etc.). See [forms-and-validation.md](forms-and-validation.md).

---

## Not-found errors (ModelNotFoundException)

`findOrFail()` and route-model binding throw `ModelNotFoundException`, which Laravel converts to a 404 page automatically. **Don't catch and re-throw** unless you have a specific error message to surface.

```php
// ✅ Let it 404
$venue = $this->model->newQuery()->findOrFail($venueId);

// ❌ Don't do this
try {
    $venue = $this->model->newQuery()->findOrFail($venueId);
} catch (ModelNotFoundException $e) {
    abort(404, 'Venue not found');  // already what Laravel does
}
```

---

## Transactions — always wrap multi-step writes

Already covered in [architecture.md](architecture.md#multi-table-writes-belong-in-the-repository). Recap:

```php
DB::beginTransaction();
try {
    // multiple writes...
    DB::commit();
} catch (Exception $exception) {
    DB::rollBack();
    throw $exception;
}
```

The `throw $exception` after rollback is mandatory — silent failures hide bugs. Let the framework or controller decide what to do with it.

---

## HTTP status codes (Inertia context)

dojohub_react uses Inertia, so most "status codes" are framework-managed:

| Situation | Response |
|-----------|----------|
| Validation failed | Redirect back + `errors` in Inertia props (302) |
| Not authenticated | Redirect to `/login` (302) |
| Not authorized | 403 page |
| Model not found | 404 page |
| Domain conflict | Redirect back + flash `error` (200/302 to user) |
| Unexpected error | 500 page + log entry |

You rarely return raw status codes — Inertia translates redirects into client-side navigations.

---

## Logging

For unexpected errors that you want to record but can recover from gracefully:

```php
try {
    $this->thirdPartyApi->sync();
} catch (Exception $e) {
    Log::error('Third-party sync failed', [
        'venue_id' => $venue->id,
        'exception' => $e->getMessage(),
    ]);
    // ... fallback behaviour
}
```

For domain exceptions caught by the controller, don't double-log — the audit log + flash message is enough.

---

## Audit log

The `Auditable` trait (auto-applied via `AppBaseModel`) writes every create/update/delete to the `audits` table. Failed operations that rolled back transactions won't appear in the audit log because the rollback reverses the audit insert too. This is the desired behaviour.

To inspect audits in development:

```bash
php artisan tinker
>>> $venue->audits()->latest()->get();
```

---

## Pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| Catching `Exception` broadly in a controller | Catch specific module exceptions only |
| Swallowing exceptions silently | Always re-throw after rollback or log |
| Throwing for validation errors | Use Form Request rules |
| Inlining `abort(404)` after a `findOrFail` already ran | `findOrFail` already does it |
| Hard-coded user-facing message in exception constructor | Pass the message at throw site so the controller can transform it |
| Forgetting `DB::rollBack()` in a catch block | Always pair `beginTransaction` + try/catch with rollBack |
| `flash('success')` and `flash('error')` set in the same response | Choose one; mixed flashes confuse the user |

---

## Related docs

- [architecture.md](architecture.md#multi-table-writes-belong-in-the-repository) — Transactions in repositories
- [forms-and-validation.md](forms-and-validation.md) — Validation error surfacing
- [wiki/Conventions.md](../../wiki/Conventions.md) — Validation conventions
- `app/Modules/Venues/Exceptions/VenueInUseException.php` — Reference implementation
