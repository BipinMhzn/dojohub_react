# Referee

**File:** `app/System/Referee/Models/Referee.php`  
**Namespace:** `App\System\Referee\Models`  
**Table:** `referees`  
**Extends:** `AppBaseModel`

A competition referee. Can have a certification level and nationality. Optionally linked to a system user account.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `user_id` | `ulid\|null` | FK → `users.id`, optional linked account |
| `full_name` | `string` | Required, max 255 |
| `nationality_code` | `string(2)\|null` | ISO-3166 alpha-2, FK → `countries.code` |
| `certification_level` | `RefereeCertificationLevelEnum\|null` | Optional certification |

---

## Casts

```php
'certification_level' => RefereeCertificationLevelEnum::class,
```

---

## RefereeCertificationLevelEnum

| Case | Value |
|------|-------|
| `Club` | `0` |
| `National` | `1` |
| `Continental` | `2` |
| `International` | `3` |

---

## RefereeRoleEnum (for match assignments)

| Case | Value |
|------|-------|
| `Center` | `0` |
| `Side` | `1` |
| `Video` | `2` |

---

## Sortable columns

`full_name`, `certification_level`, `created_at`, `updated_at`

Default sort column: `full_name`

---

## Relationships

```php
public function user(): BelongsTo
// user_id → users.id

public function country(): BelongsTo
// nationality_code → countries.code
```

---

## Eager loading

`RefereeRepository` eager-loads `country` and `user` for both list and single-record queries.

---

## Related

- [User](User.md)
- [Modules/Referees/Index.md](../Modules/Referees/Index.md)
