# ClubCoachesController

**File:** `app/Modules/Clubs/Controllers/ClubCoachesController.php`  
**Extends:** `BaseWebController`  
**Middleware:** `auth`, `verified`

Manages coach memberships within a club. Similar to `ClubPlayersController` but simpler: no PATCH for belt rank updates, and the active-membership uniqueness check is per-club (coaches can coach multiple clubs).

---

## Constructor

Injects `ClubCoachInterface` (membership repo) and `ClubInterface` (parent club).

---

## GET `/manage/clubs/{club}/coaches` — `index`

**Inertia component:** `clubs/coaches/index`

**Request:** `ClubCoachListRequest`

**Props:**
```ts
{
  club: Club,
  memberships: Paginated<ClubCoach & { coach: Pick<Coach, 'id' | 'full_name'> }>,
  availableCoaches: Pick<Coach, 'id' | 'full_name'>[],
  filters: PageFilters,
}
```

`availableCoaches` = coaches not already in an active membership at **this club** (a coach can still be active at other clubs).

---

## POST `/manage/clubs/{club}/coaches` — `store`

**Request:** `ClubCoachAssignRequest`

**Controller steps:**
1. Call `$repository->assignCoach($club, $request->validated())`
2. On `RuntimeException` (coach already active at this club): `back()->withErrors(['coach_id' => $e->getMessage()])`
3. On success: redirect to `clubs.coaches.index`

**Redirect (success):** `to_route('clubs.coaches.index', $club)->with('success', 'Coach assigned successfully.')`

---

## PUT `/manage/clubs/{club}/coaches/{membership}` — `update`

**Request:** `ClubCoachEndRequest`  
**Purpose:** End a coach's active membership (set `ended_at`).

**Redirect:** `to_route('clubs.coaches.index', $club)->with('success', 'Membership ended successfully.')`

---

## Related

- [ClubPlayersController](ClubPlayersController.md)
- [ClubRepository](ClubRepository.md)
- [System/ClubCoach](../../System/ClubCoach.md)
