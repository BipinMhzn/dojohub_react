# PlayersController

**File:** `app/Modules/Players/Controllers/PlayersController.php`  
**Extends:** `BaseWebController`  
**Traits:** `FileUpload`  
**Middleware:** `auth`, `verified`

CRUD controller for players. Supports profile photo upload.

---

## GET `/manage/players` — `index`

**Inertia component:** `players/index`

**Props:**
```ts
{
  players: Paginated<Player & { country: Country | null; currentBeltRank: BeltRank | null }>,
  filters: PageFilters,
}
```

---

## GET `/manage/players/create` — `create`

**Inertia component:** `players/create`

**Props:**
```ts
{
  countries: { code: string; name: string }[],
  beltRanks: Pick<BeltRank, 'id' | 'code' | 'name' | 'color'>[],
}
```

---

## POST `/manage/players` — `store`

**Request:** `PlayerCreateRequest`

**Validation rules:**

| Field | Rules |
|-------|-------|
| `name` | required, string, max:255 |
| `gender` | required, Enum(PlayerGenderEnum) |
| `dob` | required, date_format:Y-m-d, before_or_equal:today |
| `weight` | required, numeric, min:1, max:500 |
| `nationality_code` | nullable, string, size:2, exists:countries,code |
| `current_belt_rank_id` | nullable, string, exists:belt_ranks,id |
| `email` | nullable, email, max:255 |
| `profile_photo` | nullable, mimes:jpeg,jpg,png,svg,gif, max:5120 |
| `phone_number` | nullable, max:20, regex (phone format) |

**Custom messages:** `dob.required`, `dob.before_or_equal`

**Controller steps:**
1. If `profile_photo` file present: upload to `/images/players`, set `$inputs['profile_photo_url']`, unset `profile_photo`
2. Call `$repository->create($inputs)` (BaseRepository default create)
3. Redirect to `players.index`

**Redirect:** `to_route('players.index')->with('success', 'Player created successfully.')`

---

## GET `/manage/players/{player}` — `show`

**Inertia component:** `players/show`

**Props:**
```ts
{
  player: Player & { country: Country | null; currentBeltRank: BeltRank | null; address: Address & { country: Country } | null },
}
```

---

## GET `/manage/players/{player}/edit` — `edit`

**Inertia component:** `players/edit` (separate edit page, unlike Venues/Clubs which redirect to show)

**Props:**
```ts
{
  player: Player & { country: Country | null; currentBeltRank: BeltRank | null; address: Address | null },
  countries: { code: string; name: string }[],
  beltRanks: Pick<BeltRank, 'id' | 'code' | 'name' | 'color'>[],
}
```

---

## POST `/manage/players/{player}` — `update`

**Request:** `PlayerUpdateRequest` (extends `PlayerCreateRequest`, same rules)

**Controller steps:**
1. If `profile_photo` file present: upload, replace `profile_photo_url`
2. Call `$repository->update($player, $inputs)` (BaseRepository default update)
3. Redirect to `players.index`

**Redirect:** `to_route('players.index')->with('success', 'Player updated successfully.')`

---

## DELETE `/manage/players/{player}` — `destroy`

Calls `$repository->destroy($player)`. No exception catching — assumes no FK RESTRICT blocking deletion.

**Redirect:** `to_route('players.index')->with('success', 'Player deleted successfully.')`

---

## Related

- [PlayerRepository](PlayerRepository.md)
- [Players Index](Index.md)
- [System/Player](../../System/Player.md)
