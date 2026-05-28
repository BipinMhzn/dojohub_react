# ResultsController

**File:** `app/Modules/Tournaments/Controllers/ResultsController.php`  
**Extends:** `BaseWebController`  
**Middleware:** `auth`, `verified`

Read-only view of final tournament standings for a category.

---

## Constructor

```php
public function __construct(
    private readonly TournamentInterface $tournamentRepository,
    private readonly TournamentCategoryInterface $categoryRepository,
    private readonly TournamentResult $resultModel,
)
```

Note: injects the `TournamentResult` model directly (no repository), since it only reads.

---

## GET `/manage/tournaments/{tournament}/categories/{category}/results` — `index`

**Inertia component:** `tournaments/results/index`

**Controller steps:**
1. Loads results for the category, ordered by `position`:
   ```php
   $this->resultModel->newQuery()
       ->with(['registration.player', 'registration.club'])
       ->whereHas('registration', fn ($q) => $q->where('tournament_category_id', $categoryId))
       ->orderBy('position')
       ->get();
   ```
2. Loads tournament and category for page context

**Props:**
```ts
{
  tournament: Tournament,
  category: TournamentCategory,
  results: (TournamentResult & {
    registration: TournamentRegistration & {
      player: Player,
      club: Club | null,
    }
  })[],
}
```

---

## TournamentResult model

**Table:** `tournament_results`  
Populated by `BracketService` when matches are completed. Contains `registration_id`, `position` (final placement), and any score summary. Exact columns TBD — see the migration for the current schema.

---

## Related

- [MatchesController](MatchesController.md)
- [Tournaments Index](Index.md)
