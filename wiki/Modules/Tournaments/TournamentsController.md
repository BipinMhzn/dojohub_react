# TournamentsController

**File:** `app/Modules/Tournaments/Controllers/TournamentsController.php`  
**Extends:** `BaseWebController`  
**Traits:** `FileUpload`  
**Middleware:** `auth`, `verified`

CRUD for tournaments. Supports logo upload; status is set programmatically (not via form input).

```php
private string $uploadDirectory = '/images/tournaments';
```

---

## GET `/manage/tournaments` — `index`

**Inertia component:** `tournaments/index`

**Props:**
```ts
{
  tournaments: Paginated<Tournament & { ruleSet: TournamentRuleSet | null }>,
  filters: PageFilters,
}
```

---

## GET `/manage/tournaments/create` — `create`

**Inertia component:** `tournaments/create`

**Props:**
```ts
{
  venues: Pick<Venue, 'id' | 'name'>[],
  ruleSets: Pick<TournamentRuleSet, 'id' | 'name'>[],
}
```

---

## POST `/manage/tournaments` — `store`

**Request:** `TournamentCreateRequest`

| Field | Rules |
|-------|-------|
| `name` | required, string, max:255 |
| `description` | nullable, string |
| `logo` | nullable, mimes:jpeg,jpg,png,svg,gif, max:5120 |
| `level` | required, integer, Enum(TournamentLevelEnum) |
| `start_at` | required, date |
| `end_at` | nullable, date |
| `venue_id` | required, string, exists:venues,id (non-deleted) |
| `rule_set_id` | nullable, string, exists:tournament_rule_sets,id (non-deleted) |
| `timezone` | nullable, string, max:64, timezone |

**Custom messages:** `start_at.required` = "The start date is required", `venue_id.required` = "The venue is required"

**Controller steps:**
1. If `logo` file present: upload to `/images/tournaments`, set `$inputs['logo_url']`, unset `logo`
2. Force `$inputs['status'] = TournamentStatusEnum::Scheduled` (ignores any user-supplied status)
3. Call `$repository->create($inputs)`
4. Redirect to `tournaments.show`

**Redirect:** `to_route('tournaments.show', $tournament->id)->with('success', 'Tournament created successfully.')`

---

## GET `/manage/tournaments/{tournament}` — `show`

**Inertia component:** `tournaments/show`

**Props:**
```ts
{
  tournament: Tournament & { ruleSet: TournamentRuleSet | null },
  venues: Pick<Venue, 'id' | 'name'>[],
  ruleSets: Pick<TournamentRuleSet, 'id' | 'name'>[],
}
```

---

## GET `/manage/tournaments/{tournament}/edit` — `edit`

Redirects to `tournaments.show`. Edit form is embedded in the show page.

---

## POST `/manage/tournaments/{tournament}` — `update`

**Request:** `TournamentUpdateRequest` (same rules as create)

**Controller steps:**
1. If `logo` file present: upload, set `$inputs['logo_url']`
2. Call `$repository->update($tournament, $inputs)`
3. Redirect to `tournaments.show`

---

## DELETE `/manage/tournaments/{tournament}` — `destroy`

**Redirect:** `to_route('tournaments.index')->with('success', 'Tournament deleted successfully.')`

---

## Related

- [TournamentCategoriesController](TournamentCategoriesController.md)
- [TournamentRepository](TournamentRepository.md)
- [Tournaments Index](Index.md)
