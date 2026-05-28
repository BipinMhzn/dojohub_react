# Clubs Module

**Directory:** `app/Modules/Clubs/`  
**Routes prefix:** `/manage/clubs`  
**Route names prefix:** `clubs`

Manages martial arts clubs and their memberships. A club has players and coaches, each managed via a time-bounded membership pivot table.

---

## Controllers

| Controller | Route prefix | Inertia pages |
|-----------|-------------|---------------|
| `ClubsController` | `/manage/clubs` | `clubs/index`, `clubs/create`, `clubs/show` |
| `ClubPlayersController` | `/manage/clubs/{club}/players` | `clubs/players/index` |
| `ClubCoachesController` | `/manage/clubs/{club}/coaches` | `clubs/coaches/index` |

---

## Routes

```
GET    /manage/clubs                           → ClubsController@index         (clubs.index)
GET    /manage/clubs/create                    → ClubsController@create        (clubs.create)
POST   /manage/clubs                           → ClubsController@store         (clubs.store)
GET    /manage/clubs/{club}                    → ClubsController@show          (clubs.show)
GET    /manage/clubs/{club}/edit               → ClubsController@edit          (clubs.edit) *redirect to show
POST   /manage/clubs/{club}                    → ClubsController@update        (clubs.update)  ← POST not PUT
DELETE /manage/clubs/{club}                    → ClubsController@destroy       (clubs.destroy)

GET    /manage/clubs/{club}/players            → ClubPlayersController@index   (clubs.players.index)
POST   /manage/clubs/{club}/players            → ClubPlayersController@store   (clubs.players.store)
PUT    /manage/clubs/{club}/players/{membership} → ClubPlayersController@update (clubs.players.update)
PATCH  /manage/clubs/{club}/players/{membership} → ClubPlayersController@patch  (clubs.players.patch)

GET    /manage/clubs/{club}/coaches            → ClubCoachesController@index   (clubs.coaches.index)
POST   /manage/clubs/{club}/coaches            → ClubCoachesController@store   (clubs.coaches.store)
PUT    /manage/clubs/{club}/coaches/{membership} → ClubCoachesController@update (clubs.coaches.update)
```

All routes: `auth` + `verified` middleware.

**Note:** Clubs use `POST` for update (not `PUT`) because the update may include a logo file upload.

---

## Database tables

| Table | Model | Notes |
|-------|-------|-------|
| `clubs` | `Club` | Main club record |
| `addresses` | `Address` | Club address (optional FK `address_id`) |
| `clubs_players` | `ClubPlayer` | Player memberships (pivot) |
| `clubs_coaches` | `ClubCoach` | Coach memberships (pivot) |

---

## Service bindings

```php
$this->app->bind(ClubInterface::class, fn () => new ClubRepository(new Club, new Address));
$this->app->bind(ClubPlayerInterface::class, fn () => new ClubPlayerRepository(new ClubPlayer, new Player));
$this->app->bind(ClubCoachInterface::class, fn () => new ClubCoachRepository(new ClubCoach, new Coach));
```

---

## Module files

| File | Purpose |
|------|---------|
| `Controllers/ClubsController.php` | Club CRUD with logo upload |
| `Controllers/ClubPlayersController.php` | Assign, end, update player memberships |
| `Controllers/ClubCoachesController.php` | Assign, end coach memberships |
| `Repositories/ClubRepository.php` | Club CRUD + optional address |
| `Repositories/ClubPlayerRepository.php` | Player membership management |
| `Repositories/ClubCoachRepository.php` | Coach membership management |
| `Filters/ClubsFilter.php` | `keyword` → `name` filter |
| `Filters/ClubPlayersFilter.php` | Player membership filters |
| `Filters/ClubCoachesFilter.php` | Coach membership filters |
| `Requests/ClubCreateRequest.php` | Club create validation |
| `Requests/ClubUpdateRequest.php` | Extends create |
| `Requests/ClubPlayerAssignRequest.php` | Assign player validation |
| `Requests/ClubPlayerEndRequest.php` | End membership validation |
| `Requests/ClubPlayerUpdateRequest.php` | Update belt/certificate |
| `Requests/ClubPlayerListRequest.php` | List request for players |
| `Requests/ClubCoachAssignRequest.php` | Assign coach validation |
| `Requests/ClubCoachEndRequest.php` | End coach membership |
| `Requests/ClubCoachListRequest.php` | List request for coaches |
| `ServiceProvider.php` | Binds interfaces |

---

## Related

- [ClubsController](ClubsController.md)
- [ClubPlayersController](ClubPlayersController.md)
- [ClubCoachesController](ClubCoachesController.md)
- [ClubRepository](ClubRepository.md)
- [System/Club](../../System/Club.md)
