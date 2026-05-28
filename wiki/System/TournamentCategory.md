# TournamentCategory

**File:** `app/System/Tournament/Models/TournamentCategory.php`  
**Namespace:** `App\System\Tournament\Models`  
**Table:** `tournament_categories`  
**Extends:** `AppBaseModel`

A competition category (division) within a tournament, defined by gender, age, and weight class. Players register to categories; brackets are generated per category.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `tournament_id` | `ulid` | FK → `tournaments.id` |
| `name` | `string` | Display name |
| `gender` | `PlayerGenderEnum` | `Male=0`, `Female=1` |
| `age_category` | `AgeCategoryEnum` | See enum below |
| `min_age` | `integer\|null` | Optional age minimum override |
| `max_age` | `integer\|null` | Optional age maximum override |
| `weight_category` | `WeightCategoryEnum` | See enum below |
| `min_weight` | `decimal(2)\|null` | Optional weight minimum override |
| `max_weight` | `decimal(2)\|null` | Optional weight maximum override |
| `status` | `CategoryStatusEnum` | Set by system, starts as `Pending` |
| `registration_open_at` | `datetime\|null` | When registration opens |
| `registration_close_at` | `datetime\|null` | When registration closes |
| `start_at` | `datetime\|null` | Category start time |
| `end_at` | `datetime\|null` | Category end time |
| `weigh_in_start_at` | `datetime\|null` | Weigh-in window start |
| `weigh_in_end_at` | `datetime\|null` | Weigh-in window end |

---

## Casts

```php
'gender'                => PlayerGenderEnum::class,
'age_category'          => AgeCategoryEnum::class,
'weight_category'       => WeightCategoryEnum::class,
'status'                => CategoryStatusEnum::class,
'min_weight'            => 'decimal:2',
'max_weight'            => 'decimal:2',
'registration_open_at'  => 'datetime',
'registration_close_at' => 'datetime',
'start_at'              => 'datetime',
'end_at'                => 'datetime',
'weigh_in_start_at'     => 'datetime',
'weigh_in_end_at'       => 'datetime',
```

---

## CategoryStatusEnum

| Case | Value | Meaning |
|------|-------|---------|
| `Pending` | `0` | Open for registration |
| `Seeding` | `1` | Registration closed, seeding in progress |
| `Active` | `2` | Competition underway |
| `Completed` | `3` | All matches decided |

**Note:** Registration is only allowed when `status === CategoryStatusEnum::Pending`. This is enforced in `RegistrationsController::index()`.

---

## AgeCategoryEnum (abbreviated)

`Preparation=0`, `SuperMini=1`, `Mini=2`, `Pupils=3`, `Juveniles=4`, `Cadets=5`, `Juniors=6`, `Under23=7`, `Seniors=8`, `Veterans=9`

---

## WeightCategoryEnum (14 classes)

`Female48=0`, `Female52=1`, `Female57=2`, `Male60=3`, `Female63=4`, `Male66=5`, `Female70=6`, `Male73=7`, `Female78=8`, `Male81=9`, `Male90=10`, `Male100=11`, `Male100Plus=12`, `Female78Plus=13`

---

## Sortable columns

`name`, `created_at`, `updated_at`

---

## Relationships

```php
public function tournament(): BelongsTo
// tournament_id → tournaments.id

public function registrations(): HasMany
// tournament_category_id on tournament_registrations

public function brackets(): HasMany
// tournament_category_id on tournament_brackets
```

---

## Status on creation

`status` is always set to `CategoryStatusEnum::Pending` by `TournamentCategoryRepository::createCategory()`. It is not accepted from user input.

---

## Related

- [Tournament](Tournament.md)
- [TournamentRegistration](TournamentRegistration.md)
- [TournamentBracket](TournamentBracket.md)
- [Modules/Tournaments/TournamentCategoriesController.md](../Modules/Tournaments/TournamentCategoriesController.md)
