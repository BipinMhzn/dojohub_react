# ClubPlayersController

**File:** `app/Modules/Clubs/Controllers/ClubPlayersController.php`  
**Extends:** `BaseWebController`  
**Middleware:** `auth`, `verified`

Manages player memberships within a club. A membership is time-bounded: `started_at` → `ended_at` (null = active).

---

## Constructor

Injects `ClubPlayerInterface` (membership repo) and `ClubInterface` (parent club repo for page heading).

---

## GET `/manage/clubs/{club}/players` — `index`

**Inertia component:** `clubs/players/index`

**Request:** `ClubPlayerListRequest`

**Controller steps:**
1. Load parent club via `$clubRepository->getById($club)`
2. Load paginated memberships with player + beltRank eager-loaded
3. Load available players (not currently in any active membership) for the assign form
4. Load all belt ranks ordered by `sort_order`

**Props:**
```ts
{
  club: Club,
  memberships: Paginated<ClubPlayer & { player: Player; beltRank: BeltRank | null }>,
  availablePlayers: Pick<Player, 'id' | 'name' | 'profile_photo_url'>[],
  beltRanks: Pick<BeltRank, 'id' | 'name' | 'code' | 'color'>[],
  filters: PageFilters,
}
```

---

## POST `/manage/clubs/{club}/players` — `store`

**Request:** `ClubPlayerAssignRequest`

Assigns a player to this club.

**Controller steps:**
1. Call `$repository->assignPlayer($club, $request->validated())`
2. On `RuntimeException` (player already has active membership): `back()->withErrors(['player_id' => $e->getMessage()])`
3. On success: redirect to `clubs.players.index`

**Error case:** If the player already has an active club membership anywhere, a `RuntimeException` is thrown by the repository and returned as a `player_id` field error.

**Redirect (success):** `to_route('clubs.players.index', $club)->with('success', 'Player assigned successfully.')`

---

## PUT `/manage/clubs/{club}/players/{membership}` — `update`

**Request:** `ClubPlayerEndRequest`  
**Purpose:** End a player's active membership (set `ended_at`).

**Controller steps:**
1. Call `$repository->endMembership($club, $membership, $request->validated())`
2. Redirect to `clubs.players.index`

**Redirect:** `to_route('clubs.players.index', $club)->with('success', 'Membership ended successfully.')`

---

## PATCH `/manage/clubs/{club}/players/{membership}` — `patch`

**Request:** `ClubPlayerUpdateRequest`  
**Purpose:** Update belt rank and grade certificate on an existing membership.

**Controller steps:**
1. Call `$repository->updateMembershipDetails($club, $membership, $request->validated())`
2. Redirect to `clubs.players.index`

**Redirect:** `to_route('clubs.players.index', $club)->with('success', 'Membership updated successfully.')`

---

## Related

- [ClubCoachesController](ClubCoachesController.md)
- [ClubRepository](ClubRepository.md)
- [System/ClubPlayer](../../System/ClubPlayer.md)
