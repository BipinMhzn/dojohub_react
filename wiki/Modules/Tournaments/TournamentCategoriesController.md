# TournamentCategoriesController

**File:** `app/Modules/TournamentCategories/Controllers/TournamentCategoriesController.php`  
**Extends:** `BaseWebController`  
**Middleware:** `auth`, `verified`

CRUD for tournament categories. Located in the `TournamentCategories` module but tightly coupled to the Tournaments module (shares tournament repository).

---

## Constructor

```php
public function __construct(
    private readonly TournamentInterface $tournamentRepository,
    private readonly TournamentCategoryInterface $categoryRepository,
)
```

---

## GET `/manage/tournaments/{tournament}/categories` — `index`

**Inertia component:** `tournaments/categories/index`

**Props:**
```ts
{
  tournament: Tournament,
  categories: (TournamentCategory & { registrations_count: number })[],
}
```

Categories are ordered by `gender`, `age_category`, `weight_category`.

---

## GET `/manage/tournaments/{tournament}/categories/create` — `create`

**Inertia component:** `tournaments/categories/create`

**Props:**
```ts
{ tournament: Tournament }
```

---

## POST `/manage/tournaments/{tournament}/categories` — `store`

**Request:** `TournamentCategoryCreateRequest`

**Controller steps:**
1. Call `$categoryRepository->createCategory($tournamentId, $request->validated())`
   - Injects `tournament_id` and sets `status = CategoryStatusEnum::Pending`
2. Redirect to `tournaments.categories.index`

**Redirect:** `to_route('tournaments.categories.index', [$tournamentId])->with('success', 'Category created successfully.')`

---

## GET `/manage/tournaments/{tournament}/categories/{category}/edit` — `edit`

**Inertia component:** `tournaments/categories/edit`

**Props:**
```ts
{
  tournament: Tournament,
  category: TournamentCategory & { registrations_count: number },
}
```

---

## PUT `/manage/tournaments/{tournament}/categories/{category}` — `update`

**Request:** `TournamentCategoryUpdateRequest`

1. Call `$categoryRepository->updateCategory($categoryId, $request->validated())`
2. Redirect to `tournaments.categories.edit` (stays on edit page after save)

**Redirect:** `to_route('tournaments.categories.edit', [$tournamentId, $categoryId])->with('success', 'Category updated successfully.')`

---

## DELETE `/manage/tournaments/{tournament}/categories/{category}` — `destroy`

1. Call `$categoryRepository->deleteCategory($categoryId)`
2. Redirect to `tournaments.categories.index`

**Redirect:** `to_route('tournaments.categories.index', [$tournamentId])->with('success', 'Category deleted successfully.')`

---

## TournamentCategoryRepository methods

### `getTournamentCategories(string $tournamentId): Collection`
Ordered by `gender` → `age_category` → `weight_category`. Includes `withCount('registrations')`.

### `getCategoryById(string $categoryId): TournamentCategory`
Eager-loads `tournament.venue` and `withCount('registrations')`.

### `createCategory(string $tournamentId, array $validated): TournamentCategory`
Injects `tournament_id` and `status = Pending`. All other fields from validated input.

### `updateCategory(string $categoryId, array $validated): TournamentCategory`
Uses `fill()` + `save()` pattern.

### `deleteCategory(string $categoryId): true`
Soft-deletes the category.

---

## Related

- [RegistrationsController](RegistrationsController.md)
- [MatchesController](MatchesController.md)
- [System/TournamentCategory](../../System/TournamentCategory.md)
