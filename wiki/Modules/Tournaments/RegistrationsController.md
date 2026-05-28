# RegistrationsController

**File:** `app/Modules/Tournaments/Controllers/RegistrationsController.php`  
**Extends:** `BaseWebController`  
**Middleware:** `auth`, `verified`

Manages player registrations to a tournament category. Only allowed while category status is `Pending`.

---

## GET `/manage/tournaments/{tournament}/categories/{category}/registrations` — `index`

**Inertia component:** `tournaments/registrations/index`

**Controller steps:**
1. Load category via `$repository->getCategoryById($categoryId)`
2. Determine `$canRegister = $category->status === CategoryStatusEnum::Pending`
3. If `$canRegister`: load available players and clubs; otherwise pass empty arrays

**Props:**
```ts
{
  tournament: Tournament,
  category: TournamentCategory & { registrations_count: number },
  registrations: (TournamentRegistration & { player: Player; club: Club | null })[],
  players: Player[],    // empty if registration closed
  clubs: Club[],        // empty if registration closed
}
```

---

## POST `/manage/tournaments/{tournament}/categories/{category}/registrations` — `store`

**Request:** `TournamentRegistrationCreateRequest`

**Controller steps:**
1. Load category via `getCategoryById`
2. Load player via `getRegisteringPlayerById($category, $inputs['player_id'])` — throws `PlayerAlreadyRegisteredException` if duplicate
3. Calculate `player_age = Carbon::parse($player->dob)->age`
4. Snapshot `player_weight = $player->weight`
5. Set `tournament_category_id = $categoryId`
6. Set `status = RegistrationStatusEnum::Registered`
7. Call `$repository->create($inputs)`
8. Redirect

**Redirect:** `to_route('tournaments.categories.registrations.index', [$tournamentId, $categoryId])->with('success', 'Player registered successfully.')`

**Error case:** `PlayerAlreadyRegisteredException` is not caught in this controller — it propagates to Laravel's exception handler (returns 500 unless handled globally). Consider wrapping in try/catch for user-facing errors.

---

## DELETE `/manage/tournaments/{tournament}/categories/{category}/registrations/{registration}` — `destroy`

**Controller steps:**
1. Load category via `getCategoryById`
2. Call `$repository->destroyTournamentRegistration($category, $registrationId)`
3. Redirect

**Redirect:** `to_route('tournaments.categories.registrations.index', [$tournamentId, $categoryId])->with('success', 'Player registration removed successfully.')`

---

## Related

- [MatchesController](MatchesController.md)
- [System/TournamentRegistration](../../System/TournamentRegistration.md)
- [System/TournamentCategory](../../System/TournamentCategory.md)
