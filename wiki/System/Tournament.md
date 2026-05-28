# Tournament

**File:** `app/System/Tournament/Models/Tournament.php`  
**Namespace:** `App\System\Tournament\Models`  
**Table:** `tournaments`  
**Extends:** `AppBaseModel`

The root aggregate for a competition event. A tournament has a venue, a rule set, and many categories. Status is set to `Scheduled` on creation and progresses through `InProgress` → `Completed` or `Cancelled`.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `name` | `string` | Required, max 255 |
| `description` | `string\|null` | Free text |
| `logo_url` | `string\|null` | Public storage URL |
| `level` | `TournamentLevelEnum` | Required |
| `start_at` | `datetime` | Required |
| `end_at` | `datetime\|null` | Optional end date |
| `status` | `TournamentStatusEnum` | Set by system, starts as `Scheduled` |
| `venue_id` | `ulid` | Required FK → `venues.id` |
| `rule_set_id` | `ulid\|null` | Optional FK → `tournament_rule_sets.id` |
| `timezone` | `string\|null` | IANA timezone string (e.g. `"Asia/Kathmandu"`) |

---

## Casts

```php
'level'    => TournamentLevelEnum::class,
'status'   => TournamentStatusEnum::class,
'start_at' => 'datetime',
'end_at'   => 'datetime',
```

---

## TournamentLevelEnum

| Case | Value |
|------|-------|
| `Local` | `1` |
| `Regional` | `2` |
| `National` | `3` |
| `International` | `4` |

---

## TournamentStatusEnum

| Case | Value |
|------|-------|
| `Scheduled` | `0` |
| `InProgress` | `1` |
| `Completed` | `2` |
| `Cancelled` | `3` |

---

## Sortable columns

`name`, `created_at`, `updated_at`

---

## Relationships

```php
public function venue(): BelongsTo
// venue_id → venues.id

public function ruleSet(): BelongsTo
// rule_set_id → tournament_rule_sets.id

public function categories(): HasMany
// tournament_id on tournament_categories
```

---

## Status management

`status` is **not set from user input** in create/update requests. It is set programmatically:

```php
// In TournamentsController::store()
$inputs['status'] = TournamentStatusEnum::Scheduled;
```

Status transitions are not yet automated — manually updated or through future workflow actions.

---

## Logo upload

Handled by `FileUpload` trait in `TournamentsController`, stored under `/images/tournaments`.

---

## Related

- [TournamentCategory](TournamentCategory.md)
- [TournamentRuleSet](TournamentRuleSet.md)
- [Venue](Venue.md)
- [Modules/Tournaments/Index.md](../Modules/Tournaments/Index.md)
