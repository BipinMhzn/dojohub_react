# Players Module

**Directory:** `app/Modules/Players/`  
**Routes prefix:** `/manage/players`  
**Route names prefix:** `players`

Manages athlete records. Players can have profile photos, nationality, belt rank, and club memberships.

---

## Controller

| Controller | Route prefix | Inertia pages |
|-----------|-------------|---------------|
| `PlayersController` | `/manage/players` | `players/index`, `players/create`, `players/edit`, `players/show` |

---

## Routes

```
GET    /manage/players               → PlayersController@index   (players.index)
GET    /manage/players/create        → PlayersController@create  (players.create)
POST   /manage/players               → PlayersController@store   (players.store)
GET    /manage/players/{player}      → PlayersController@show    (players.show)
GET    /manage/players/{player}/edit → PlayersController@edit    (players.edit)
POST   /manage/players/{player}      → PlayersController@update  (players.update)  ← POST not PUT
DELETE /manage/players/{player}      → PlayersController@destroy (players.destroy)
```

All routes: `auth` + `verified` middleware.

**Note:** Update uses `POST` (not `PUT`) because the form may include a profile photo file.

---

## Database tables

| Table | Model | Notes |
|-------|-------|-------|
| `players` | `Player` | Main player record |
| `addresses` | `Address` | Optional player address |

---

## Service binding

```php
$this->app->bind(PlayerInterface::class, fn () => new PlayerRepository(new Player));
```

---

## Related

- [PlayersController](PlayersController.md)
- [PlayerRepository](PlayerRepository.md)
- [System/Player](../../System/Player.md)
