# Coach

**File:** `app/System/Coach/Models/Coach.php`  
**Namespace:** `App\System\Coach\Models`  
**Table:** `coaches`  
**Extends:** `AppBaseModel`

A coach who can be linked to one or more clubs. Optionally associated with a system `User` account.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `user_id` | `ulid\|null` | FK → `users.id`, optional linked account |
| `full_name` | `string` | Required, max 255 |
| `email` | `string\|null` | Optional contact email |
| `phone_number` | `string\|null` | Optional, validated phone format |

---

## Sortable columns

`full_name`, `created_at`, `updated_at`

The default sort column is `full_name` (set via `protected string $defaultSortColumn = 'full_name'`).

---

## Relationships

```php
public function user(): BelongsTo
// user_id → users.id (optional linked login account)

public function memberships(): HasMany
// clubs_coaches, all memberships

public function activeMemberships(): HasMany
// clubs_coaches WHERE ended_at IS NULL

public function clubs(): BelongsToMany
// clubs_coaches pivot: id, started_at, ended_at
```

---

## User linking

A coach can optionally be linked to a `User` account via `user_id`. This allows coaches to log in and potentially access restricted areas. The link is optional — many coaches are data-only records without a login.

---

## Eager loading

`CoachRepository` always eager-loads `user` and `activeMemberships.club` for both list and single-record queries.

---

## Related

- [ClubCoach](ClubCoach.md) — club membership pivot
- [User](User.md)
- [Modules/Coaches/Index.md](../Modules/Coaches/Index.md)
