# CoachesController

**File:** `app/Modules/Coaches/Controllers/CoachesController.php`  
**Extends:** `BaseWebController`  
**Middleware:** `auth`, `verified`

Standard CRUD for coaches. No file upload — uses PUT for update.

---

## GET `/manage/coaches` — `index`

**Inertia component:** `coaches/index`

**Props:**
```ts
{
  coaches: Paginated<Coach & { user: User | null; activeMemberships: (ClubCoach & { club: Club })[] }>,
  filters: PageFilters,
}
```

---

## GET `/manage/coaches/create` — `create`

**Inertia component:** `coaches/create`

No extra props — form only requires name, email, phone, optional user link.

---

## POST `/manage/coaches` — `store`

**Request:** `CoachCreateRequest`

| Field | Rules |
|-------|-------|
| `full_name` | required, string, max:255 |
| `email` | nullable, email, max:255 |
| `phone_number` | nullable, max:20, regex (phone format) |
| `user_id` | nullable, string, exists:users,id |

**Controller steps:**
1. Call `$repository->create($request->validated())` (base class)
2. Redirect to `coaches.index`

**Redirect:** `to_route('coaches.index')->with('success', 'Coach created successfully.')`

---

## GET `/manage/coaches/{coach}/edit` — `edit`

**Inertia component:** `coaches/edit`

**Props:**
```ts
{
  coach: Coach & { user: User | null; activeMemberships: (ClubCoach & { club: Club })[] },
}
```

---

## PUT `/manage/coaches/{coach}` — `update`

**Request:** `CoachUpdateRequest` (extends `CoachCreateRequest`)

1. Call `$repository->update($coach, $request->validated())`
2. Redirect to `coaches.index`

**Redirect:** `to_route('coaches.index')->with('success', 'Coach updated successfully.')`

---

## DELETE `/manage/coaches/{coach}` — `destroy`

Calls `$repository->destroy($coach)`.

**Redirect:** `to_route('coaches.index')->with('success', 'Coach deleted successfully.')`

---

## Related

- [CoachRepository](CoachRepository.md)
- [Coaches Index](Index.md)
