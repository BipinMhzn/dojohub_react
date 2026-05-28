# User

**File:** `app/System/User/Models/User.php`  
**Namespace:** `App\System\User\Models`  
**Table:** `users`  
**Extends:** `BaseSystem\Foundation\Database\BaseModel` (NOT `AppBaseModel`)

The authenticated user model. Used by Laravel's auth stack and by the `EditorLogs` trait on all other models to record who created/updated/deleted records.

---

## Why it doesn't extend AppBaseModel

`User` implements Laravel's auth contracts (`AuthenticatableContract`, `AuthorizableContract`, `CanResetPasswordContract`). These require specific method signatures that conflict with `AppBaseModel`'s trait stack. `User` uses the base `BaseModel` directly and adds auth traits manually.

---

## Traits

| Trait | Provides |
|-------|---------|
| `Authenticatable` | `Auth::user()` session management |
| `Authorizable` | Gate/policy checks |
| `CanResetPassword` | Password reset email flow |
| `MustVerifyEmail` | Email verification middleware |
| `HasFactory` | `User::factory()` for tests |
| `HasRoles` (Spatie Permission) | `user->hasRole()`, `user->assignRole()` |
| `Notifiable` | `user->notify()` (email, etc.) |

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `name` | `string` | Display name |
| `email` | `string` | Unique, used for login |
| `password` | `string` | Hashed on save |

---

## Hidden (serialization)

`password`, `remember_token`

---

## Casts

```php
'email_verified_at' => 'datetime',
'password'          => 'hashed',   // auto-hashes on assignment
```

---

## Auth usage

Standard Laravel auth (not NicoAuth). All routes use `auth` + `verified` middleware:

```php
// routes/web.php (Modules)
Route::group(['middleware' => ['auth', 'verified'], 'prefix' => 'manage'], function () { ... });
```

Current user in controllers:

```php
Auth::user();   // App\System\User\Models\User|null
request()->user(); // same
```

---

## Roles (Spatie Permission)

User roles control access via the Spatie `laravel-permission` package. Role names are application-defined and not hard-coded in this model.

```php
$user->assignRole('admin');
$user->hasRole('admin');        // true/false
$user->getRoleNames();          // Collection of role name strings
```

---

## Related

- [AppBaseModel](AppBaseModel.md) — `User` is referenced by `editorProvider()` on all other models
- [architecture.md](../../.claude/guidelines/architecture.md#authentication)
