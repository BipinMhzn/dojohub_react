# Club

**File:** `app/System/Club/Models/Club.php`  
**Namespace:** `App\System\Club\Models`  
**Table:** `clubs`  
**Extends:** `AppBaseModel`

A martial arts club that can have member players and coaches. Clubs participate in tournaments by registering their players to categories.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `name` | `string` | Club name, required, max 255 |
| `abbreviation` | `string` | Uppercase alpha, max 10 chars (e.g. `"SJJC"`) |
| `logo_url` | `string\|null` | Public storage URL, set by file upload |
| `address_id` | `ulid\|null` | FK → `addresses.id`, optional |
| `phone_number` | `string\|null` | Validated phone format, max 20 |

---

## Sortable columns

`name`, `created_at`, `updated_at`

---

## Relationships

```php
public function address(): BelongsTo
// address_id → addresses.id

public function memberships(): HasMany
// clubs_players, all (active + ended)

public function activeMemberships(): HasMany
// clubs_players WHERE ended_at IS NULL

public function players(): BelongsToMany
// clubs_players pivot: id, started_at, ended_at, belt_rank_id, grade_certificate

public function coachMemberships(): HasMany
// clubs_coaches, all

public function activeCoachMemberships(): HasMany
// clubs_coaches WHERE ended_at IS NULL

public function coaches(): BelongsToMany
// clubs_coaches pivot: id, started_at, ended_at
```

---

## Logo upload

Logo is handled by the `FileUpload` trait in `ClubsController`:

```php
private string $uploadDirectory = '/images/clubs';
```

On update, if no new file is uploaded, the existing `logo_url` is preserved:

```php
'logo_url' => $validated['logo_url'] ?? $club->logo_url,
```

---

## Related

- [ClubPlayer](ClubPlayer.md) — player membership pivot
- [ClubCoach](ClubCoach.md) — coach membership pivot
- [Address](Address.md)
- [Modules/Clubs/Index.md](../Modules/Clubs/Index.md)
