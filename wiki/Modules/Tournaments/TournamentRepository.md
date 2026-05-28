# Tournament Repositories

---

## TournamentRepository

**File:** `app/Modules/Tournaments/Repositories/TournamentRepository.php`  
**Implements:** `TournamentInterface`  
**Extends:** `BaseRepository`

### `onBeforeResult(Builder $builder): void`

```php
$builder->with('ruleSet');
```

### `getById(string $id): Tournament`

```php
$this->model->newQuery()->with('ruleSet')->findOrFail($id);
```

### Inherited operations

`create()`, `update()`, `destroy()` — all base class defaults. No transaction needed (tournaments write to one table).

---

## BracketRepository

**File:** `app/Modules/Tournaments/Repositories/BracketRepository.php`  
**Implements:** `BracketInterface`

### `getBracketByCategory(string $categoryId): ?TournamentBracket`

Returns the first bracket for a category, or `null` if none exists. Used to guard against duplicate generation.

### `createBracket(string $categoryId, array $validated): TournamentBracket`

Creates bracket stub with `type`, `seeding_method` from validated input. Sets `status = Pending`. `competitor_count`, `round_count`, and `generated_at` are set later by `BracketService::generateBracket()`.

---

## RegistrationRepository

**File:** `app/Modules/Tournaments/Repositories/RegistrationRepository.php`  
**Implements:** `RegistrationInterface`

### `getTournamentById(string $tournamentId): Tournament`

Loads tournament for the registrations page header.

### `getCategoryById(string $categoryId): TournamentCategory`

Loads category with eager-loaded `tournament.venue` and `registrations_count`.

### `getCategoryRegistrations(TournamentCategory $category): Collection`

Returns all registrations with `player` and `club` eager-loaded.

### `getPlayerList(TournamentCategory $category): Collection`

Returns players NOT already registered in this category.

### `getClubList(): Collection`

Returns all clubs for the registration form dropdown.

### `getRegisteringPlayerById(TournamentCategory $category, string $playerId): Player`

Finds the player and throws `PlayerAlreadyRegisteredException` if already registered in the category.

### `destroyTournamentRegistration(TournamentCategory $category, string $registrationId): true`

Soft-deletes the registration. Scoped to category for safety.

---

## MatchRepository

**File:** `app/Modules/Tournaments/Repositories/MatchRepository.php`  
**Implements:** `MatchInterface`

Provides `getById()` and any match-level queries needed outside `BracketService`.

---

## TournamentsFilter

**File:** `app/Modules/Tournaments/Filters/TournamentsFilter.php`

```php
public function keyword(string $keyword = ''): void
{
    $this->name($keyword); // delegates to BaseFilter::name()
}
```

---

## Related

- [TournamentsController](TournamentsController.md)
- [MatchesController](MatchesController.md)
- [System/Tournament](../../System/Tournament.md)
