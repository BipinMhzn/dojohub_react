# VenuesController

**File:** `app/Modules/Venues/Controllers/VenuesController.php`  
**Extends:** `BaseWebController`  
**Middleware:** `auth`, `verified` (all actions)

CRUD controller for venues. Read actions render Inertia pages; mutations redirect with flash messages.

---

## GET `/manage/venues` — `index`

**Inertia component:** `venues/index`

**Request:** `BaseWebListRequest`  
Accepts: `keyword`, `sort_by`, `sort_order`, `per_page`, `page` (all optional, validated by base class)

**Controller steps:**
1. Calls `$repository->getList($request->validated())`
2. Renders with `venues` (paginated) and `filters` (current filter values)

**Props passed:**
```ts
{
  venues: Paginated<Venue & { address: Address & { country: Country }; mats_count: number }>,
  filters: PageFilters,
}
```

---

## GET `/manage/venues/create` — `create`

**Inertia component:** `venues/create`

**Controller steps:**
1. Fetches all countries ordered by name (projecting `code`, `name` only)
2. Renders form page

**Props passed:**
```ts
{ countries: { code: string; name: string }[] }
```

---

## POST `/manage/venues` — `store`

**Request:** `VenueCreateRequest`

**Validation rules:**

| Field | Rules |
|-------|-------|
| `name` | required, string, max:250 |
| `address_line_1` | required, string, max:250 |
| `address_line_2` | nullable, string, max:250 |
| `city` | required, string, max:100 |
| `state` | nullable, string, max:100 |
| `postal_code` | nullable, string, max:20 |
| `country_code` | required, string, size:2, exists:countries,code |
| `latitude` | nullable, numeric, between:-90,90 |
| `longitude` | nullable, numeric, between:-180,180 |

**Controller steps:**
1. Calls `$repository->createVenue($request->validated())`
2. Redirects to `venues.show` with `success` flash

**Redirect:** `to_route('venues.show', $venue->id)->with('success', 'Venue created successfully.')`

---

## GET `/manage/venues/{venue}` — `show`

**Inertia component:** `venues/show`

**Controller steps:**
1. Calls `$repository->getById($venue)` — eager loads `address.country` + `mats`
2. Fetches all countries

**Props passed:**
```ts
{
  venue: Venue & { address: Address & { country: Country }; mats: VenueMat[] },
  countries: { code: string; name: string }[],
}
```

---

## GET `/manage/venues/{venue}/edit` — `edit`

**Redirects** to `venues.show`. The edit form is embedded in the show page.

---

## PUT `/manage/venues/{venue}` — `update`

**Request:** `VenueUpdateRequest` (same rules as create)

**Controller steps:**
1. Calls `$repository->updateVenue($request->validated(), $venue)`
2. Redirects to `venues.show`

**Redirect:** `to_route('venues.show', $venue)->with('success', 'Venue updated successfully.')`

---

## DELETE `/manage/venues/{venue}` — `destroy`

**Controller steps:**
1. Calls `$repository->deleteVenue($venue)`
2. On success: redirect to `venues.index` with `success` flash
3. On `VenueInUseException`: `back()->with('error', $e->getMessage())`

**Redirect (success):** `to_route('venues.index')->with('success', 'Venue deleted successfully.')`  
**Redirect (error):** `back()->with('error', ...)`

---

## Related

- [VenueMatController](VenueMatController.md)
- [VenueRepository](VenueRepository.md)
- [VenueCreateRequest / VenueUpdateRequest](VenueRequests.md)
- [Venues Index](Index.md)
