# Club Repositories

---

## ClubRepository

**File:** `app/Modules/Clubs/Repositories/ClubRepository.php`  
**Implements:** `ClubInterface`  
**Extends:** `BaseRepository`

### `onBeforeResult(Builder $builder): void`

```php
$builder->with('address.country');
```

Every list item includes address + country.

### `getById(string $id): Club`

```php
$this->model->newQuery()->with('address.country')->findOrFail($id);
```

### `createClub(array $validated): Club`

- If `address_line_1` is non-empty: creates an `addresses` row and links it
- Creates the `clubs` row with `logo_url`, `name`, `abbreviation`, `phone_number`, `address_id`
- Note: **no DB transaction** here (compare to Venue which uses one). Address creation and club creation are sequential but not wrapped in a transaction

### `updateClub(array $validated, string $clubId): Club`

- Loads club via `findOrFail`
- If `address_line_1` is non-empty:
  - If address exists: updates it
  - If no address yet: creates a new one
- Updates club fields
- **Logo preservation:** `'logo_url' => $validated['logo_url'] ?? $club->logo_url`

### `deleteClub(string $clubId): true`

Soft-deletes address (if present) then soft-deletes club. No transaction.

---

## ClubPlayerRepository

**File:** `app/Modules/Clubs/Repositories/ClubPlayerRepository.php`  
**Implements:** `ClubPlayerInterface`  
**Extends:** `BaseRepository`

### `getClubPlayers(string $clubId, array $filters): LengthAwarePaginator`

Paginated list of memberships for a specific club:
- Eager-loads `player` (select: `id, name, profile_photo_url`) and `beltRank`
- Filters applied via `ClubPlayersFilter`
- Allows sort by `started_at` or `ended_at`

### `getAvailablePlayers(string $clubId): Collection`

Returns players NOT in any active membership (globally, not just this club):

```php
$activeMemberIds = $this->model->newQuery()->whereNull('ended_at')->pluck('player_id');
// Returns players not in that set, ordered by name
```

### `assignPlayer(string $clubId, array $validated): ClubPlayer`

Checks globally that the player has no active membership anywhere (`whereNull('ended_at')` across all clubs). Throws `\RuntimeException` if already active.

Creates row with: `club_id`, `player_id`, `started_at`, `belt_rank_id?`, `grade_certificate?`

### `endMembership(string $clubId, string $membershipId, array $validated): ClubPlayer`

Updates `ended_at` on the specific membership row (scoped to `club_id` for safety).

### `updateMembershipDetails(string $clubId, string $membershipId, array $validated): ClubPlayer`

Updates `belt_rank_id` and `grade_certificate` on the membership.

---

## ClubCoachRepository

**File:** `app/Modules/Clubs/Repositories/ClubCoachRepository.php`  
**Implements:** `ClubCoachInterface`

### `getClubCoaches(string $clubId, array $filters): LengthAwarePaginator`

Paginated coach memberships for a club. Eager-loads `coach` (id, full_name).

### `getAvailableCoaches(string $clubId): Collection`

Returns coaches NOT in an active membership at **this club** (per-club check, unlike players which is global):

```php
$activeMemberIds = $this->model->newQuery()
    ->where('club_id', $clubId)   // ← scoped to THIS club
    ->whereNull('ended_at')
    ->pluck('coach_id');
```

### `assignCoach(string $clubId, array $validated): ClubCoach`

Per-club duplicate check. Creates row with `club_id`, `coach_id`, `started_at`.

### `endMembership(string $clubId, string $membershipId, array $validated): ClubCoach`

Sets `ended_at` on the coach membership.

---

## Related

- [ClubsController](ClubsController.md)
- [ClubPlayersController](ClubPlayersController.md)
- [ClubCoachesController](ClubCoachesController.md)
- [System/Club](../../System/Club.md)
