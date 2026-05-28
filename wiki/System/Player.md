# Player

**File:** `app/System/Player/Models/Player.php`  
**Namespace:** `App\System\Player\Models`  
**Table:** `players`  
**Extends:** `AppBaseModel`

A competition athlete. Players belong to clubs (via `clubs_players`), register for tournament categories, and compete in matches.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `name` | `string` | Full name, required, max 255 |
| `gender` | `PlayerGenderEnum` | `Male=0`, `Female=1`, required |
| `dob` | `date` | Date of birth, format `Y-m-d`, required |
| `weight` | `decimal(2)` | Weight in kg, required, 1–500 |
| `nationality_code` | `string(2)\|null` | ISO-3166 alpha-2, FK → `countries.code` |
| `current_belt_rank_id` | `ulid\|null` | FK → `belt_ranks.id` |
| `address_id` | `ulid\|null` | FK → `addresses.id` |
| `email` | `string\|null` | Email address |
| `profile_photo_url` | `string\|null` | Public storage URL for photo |
| `phone_number` | `string\|null` | Max 20 chars, validated phone format |

---

## Casts

```php
'gender' => PlayerGenderEnum::class,
'weight' => 'decimal:2',
```

---

## PlayerGenderEnum

| Case | Value |
|------|-------|
| `Male` | `0` |
| `Female` | `1` |

---

## Sortable columns

`name`, `created_at`, `updated_at`

---

## Relationships

```php
public function country(): BelongsTo
// nationality_code → countries.code

public function currentBeltRank(): BelongsTo
// current_belt_rank_id → belt_ranks.id

public function address(): BelongsTo
// address_id → addresses.id

public function memberships(): HasMany
// clubs_players, all memberships

public function clubs(): BelongsToMany
// clubs_players pivot: id, started_at, ended_at, belt_rank_id, grade_certificate
```

---

## Profile photo upload

Photo is handled by `FileUpload` trait in `PlayersController`, stored under `/images/players`:

```php
if ($request->file('profile_photo')) {
    $photo = $this->uploadFile($request->file('profile_photo'), '/images/players');
    $inputs['profile_photo_url'] = $photo['path'];
    unset($inputs['profile_photo']);
}
```

---

## Related

- [ClubPlayer](ClubPlayer.md) — membership pivot
- [BeltRank](BeltRank.md)
- [Address](Address.md)
- [Modules/Players/Index.md](../Modules/Players/Index.md)
