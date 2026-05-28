# HandleAppearance Middleware — Wiki

**File**: `app/Http/Middleware/HandleAppearance.php`  
**Applied to**: All web routes (appended to `web` middleware group)

---

## Purpose

Reads the user's appearance (theme) preference from the `appearance` cookie and shares it with all Blade views via `View::share()`. This allows the initial HTML render to apply the correct colour scheme before React hydrates.

---

## Behaviour

```php
$appearance = $request->cookie('appearance', 'system');
View::share('appearance', $appearance);
```

| Cookie value | Meaning |
|-------------|---------|
| `'light'` | Force light mode |
| `'dark'` | Force dark mode |
| `'system'` | Follow OS preference (default) |

If the cookie is absent, defaults to `'system'`.

---

## Cookie Configuration

`appearance` is in the **unencrypted cookies** list in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->encryptCookies(except: [
        'appearance',
        'sidebar_state',
    ]);
})
```

Unencrypted so JavaScript can read and write the cookie directly without involving PHP decryption.

---

## Where It's Used

The shared `appearance` variable is consumed in the root Blade template (`resources/views/app.blade.php`) to set an inline script or class on `<html>` before React boots, preventing the flash-of-wrong-theme on first paint.

---

## Frontend Counterpart

The `useAppearance` hook in React manages the client-side appearance state, reads/writes the `appearance` cookie, and applies the appropriate class to the `<html>` element.

---

## Related

- [HandleInertiaRequests](HandleInertiaRequests.md) — shares other global props (auth, flash, ziggy)
- [useAppearance hook](../../Hooks/useAppearance.md) — React hook that reads/writes the appearance cookie
