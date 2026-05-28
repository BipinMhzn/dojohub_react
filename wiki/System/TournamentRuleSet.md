# TournamentRuleSet

**File:** `app/System/TournamentRuleSet/Models/TournamentRuleSet.php`  
**Namespace:** `App\System\TournamentRuleSet\Models`  
**Table:** `tournament_rule_sets`  
**Extends:** `AppBaseModel`

A named rule set defining scoring limits and match duration for a tournament. Applied at the tournament level and used by the scoring engine during match play.

---

## Fillable

| Column | Type | Notes |
|--------|------|-------|
| `name` | `string` | Display name (e.g. "IJF 2024 Rules") |
| `max_waza_ari` | `integer` | Number of waza-ari that constitutes ippon |
| `max_shido` | `integer` | Number of shidos before hansoku-make |
| `match_duration_seconds` | `integer` | Regular match time in seconds |
| `golden_score_seconds` | `integer` | Overtime (golden score) duration in seconds |

---

## Casts

```php
'max_waza_ari'            => 'integer',
'max_shido'               => 'integer',
'match_duration_seconds'  => 'integer',
'golden_score_seconds'    => 'integer',
```

---

## Usage

Rule sets are selected on tournament creation. In `TournamentsController`:

```php
'ruleSets' => TournamentRuleSet::all(['id', 'name']),
```

The rule set is eager-loaded by `TournamentRepository` on both list and single-record queries:

```php
public function onBeforeResult(Builder $builder): void
{
    $builder->with('ruleSet');
}
```

---

## No factory

`TournamentRuleSet` does not define `newFactory()` — it is treated as seed/reference data rather than factory-generated test data.

---

## Related

- [Tournament](Tournament.md)
- [Modules/Tournaments/Index.md](../Modules/Tournaments/Index.md)
