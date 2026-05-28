# ClubsController

**File:** `app/Modules/Clubs/Controllers/ClubsController.php`  
**Extends:** `BaseWebController`  
**Traits:** `FileUpload`  
**Middleware:** `auth`, `verified`

CRUD controller for clubs. Supports optional logo upload. Uses POST for update (not PUT) due to file upload limitations in HTML forms.

```php
private string $uploadDirectory = '/images/clubs';
```

---

## GET `/manage/clubs` — `index`

**Inertia component:** `clubs/index`

**Props:**
```ts
{
  clubs: Paginated<Club & { address: Address & { country: Country } }>,
  filters: PageFilters,
}
```

---

## GET `/manage/clubs/create` — `create`

**Inertia component:** `clubs/create`

**Props:**
```ts
{ countries: { code: string; name: string }[] }
```

---

## POST `/manage/clubs` — `store`

**Request:** `ClubCreateRequest`

**Validation rules:**

| Field | Rules |
|-------|-------|
| `name` | required, string, max:255 |
| `abbreviation` | required, string, uppercase, alpha, max:10 |
| `logo` | nullable, mimes:jpeg,jpg,png,gif,svg, max:5120 |
| `phone_number` | nullable, max:20, regex (phone format) |
| `address_line_1` | nullable, string, max:255 |
| `address_line_2` | nullable, string, max:255 |
| `city` | nullable, string, max:100 |
| `state` | nullable, string, max:100 |
| `postal_code` | nullable, string, max:20 |
| `country_code` | nullable, string, size:2, exists:countries,code |

**Controller steps:**
1. If `logo` file present: upload to `/images/clubs`, set `$inputs['logo_url'] = $path`, `unset($inputs['logo'])`
2. Call `$repository->createClub($inputs)`
3. Redirect to `clubs.show`

**Redirect:** `to_route('clubs.show', $club->id)->with('success', 'Club created successfully.')`

**Frontend form note:** Must use `forceFormData: true` on Inertia submit (logo is a File).

---

## GET `/manage/clubs/{club}/edit` — `edit`

Redirects to `clubs.show`. Edit form is embedded in the show page.

---

## POST `/manage/clubs/{club}` — `update`

**Request:** `ClubUpdateRequest` (same rules as create)

**Controller steps:**
1. If `logo` file present: upload, set `$inputs['logo_url']`, unset `logo`
2. Call `$repository->updateClub($inputs, $club)`
3. Redirect to `clubs.show`

**Logo preservation:** If no new logo uploaded, repository falls back to `$club->logo_url`.

**Redirect:** `to_route('clubs.show', $club)->with('success', 'Club updated successfully.')`

---

## GET `/manage/clubs/{club}` — `show`

**Inertia component:** `clubs/show`

**Props:**
```ts
{
  club: Club & { address: Address & { country: Country } },
  countries: { code: string; name: string }[],
}
```

---

## DELETE `/manage/clubs/{club}` — `destroy`

Calls `$repository->deleteClub($club)`, redirects to `clubs.index`.

**Redirect:** `to_route('clubs.index')->with('success', 'Club deleted successfully.')`

---

## Related

- [ClubPlayersController](ClubPlayersController.md)
- [ClubCoachesController](ClubCoachesController.md)
- [ClubRepository](ClubRepository.md)
- [Clubs Index](Index.md)
