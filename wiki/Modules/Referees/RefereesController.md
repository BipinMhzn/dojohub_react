# RefereesController

**File:** `app/Modules/Referees/Controllers/RefereesController.php`  
**Extends:** `BaseWebController`  
**Middleware:** `auth`, `verified`

Standard CRUD for referees. Uses PUT for update (no file upload).

---

## GET `/manage/referees` — `index`

**Inertia component:** `referees/index`

**Props:**
```ts
{
  referees: Paginated<Referee & { country: Country | null; user: User | null }>,
  filters: PageFilters,
}
```

---

## GET `/manage/referees/create` — `create`

**Inertia component:** `referees/create`

**Props:**
```ts
{ countries: { code: string; name: string }[] }
```

---

## POST `/manage/referees` — `store`

**Request:** `RefereeCreateRequest`

| Field | Rules |
|-------|-------|
| `full_name` | required, string, max:255 |
| `nationality_code` | nullable, string, size:2, exists:countries,code |
| `certification_level` | nullable, Enum(RefereeCertificationLevelEnum) |
| `user_id` | nullable, string, exists:users,id |

**Controller steps:**
1. Call `$repository->create($request->validated())`
2. Redirect to `referees.index`

**Redirect:** `to_route('referees.index')->with('success', 'Referee created successfully.')`

---

## GET `/manage/referees/{referee}/edit` — `edit`

**Inertia component:** `referees/edit`

**Props:**
```ts
{
  referee: Referee & { country: Country | null; user: User | null },
  countries: { code: string; name: string }[],
}
```

---

## PUT `/manage/referees/{referee}` — `update`

**Request:** `RefereeUpdateRequest` (extends `RefereeCreateRequest`)

1. Call `$repository->update($referee, $request->validated())`
2. Redirect to `referees.index`

**Redirect:** `to_route('referees.index')->with('success', 'Referee updated successfully.')`

---

## DELETE `/manage/referees/{referee}` — `destroy`

**Redirect:** `to_route('referees.index')->with('success', 'Referee deleted successfully.')`

---

## Related

- [RefereeRepository](RefereeRepository.md)
- [Referees Index](Index.md)
- [System/Referee](../../System/Referee.md)
