# VenueMatController

**File:** `app/Modules/Venues/Controllers/VenueMatController.php`  
**Extends:** `BaseWebController`  
**Middleware:** `auth`, `verified` (all actions)

Manages mats within a venue. Mats are listed/created/updated/deleted from a sub-page nested under the parent venue.

---

## Constructor

Injects both `VenueMatInterface` and `VenueInterface` — the parent venue is needed to load the page heading.

---

## GET `/manage/venues/{venue}/mats` — `index`

**Inertia component:** `venues/mats/index`

**Controller steps:**
1. Loads the parent venue via `$venueRepository->getById($venue)` (eager loads `address.country` + `mats`)
2. Renders page — mats are included within the `venue.mats` relation

**Props passed:**
```ts
{
  venue: Venue & { mats: VenueMat[]; address: Address & { country: Country } },
}
```

---

## POST `/manage/venues/{venue}/mats` — `store`

**Request:** `VenueMatRequest`

**Validation rules:**

| Field | Rules |
|-------|-------|
| `mat_number` | required, integer, min:1, max:99, unique per venue (excluding soft-deleted, no ignore on create) |
| `display_name` | nullable, string, max:50 |

**Controller steps:**
1. Calls `$repository->createMat($venue, $request->validated())`
2. Redirects to `venues.mats.index`

**Redirect:** `to_route('venues.mats.index', $venue)->with('success', 'Mat added.')`

---

## PUT `/manage/venues/{venue}/mats/{mat}` — `update`

**Request:** `VenueMatRequest`

The `unique` rule on `mat_number` uses `->ignore($this->route('mat'))` to exclude the current mat from the uniqueness check.

**Controller steps:**
1. Calls `$repository->updateMat($mat, $request->validated())`
2. Redirects to `venues.mats.index`

**Redirect:** `to_route('venues.mats.index', $venue)->with('success', 'Mat updated.')`

---

## DELETE `/manage/venues/{venue}/mats/{mat}` — `destroy`

**Controller steps:**
1. Calls `$repository->deleteMat($mat)`
2. On success: redirect to `venues.mats.index`
3. On `VenueMatInUseException`: `back()->with('error', $e->getMessage())`

**Redirect (success):** `to_route('venues.mats.index', $venue)->with('success', 'Mat removed.')`  
**Redirect (error):** `back()->with('error', ...)`

---

## Related

- [VenuesController](VenuesController.md)
- [VenueRepository](VenueRepository.md)
- [VenueRequests](VenueRequests.md)
- [System/VenueMat](../../System/VenueMat.md)
