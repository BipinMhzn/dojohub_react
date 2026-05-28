# Referees Module

**Directory:** `app/Modules/Referees/`  
**Routes prefix:** `/manage/referees`  
**Route names prefix:** `referees`

Manages referee records. No `show` route — list and edit are the primary views.

---

## Controller

| Controller | Route prefix | Inertia pages |
|-----------|-------------|---------------|
| `RefereesController` | `/manage/referees` | `referees/index`, `referees/create`, `referees/edit` |

---

## Routes

```
GET    /manage/referees                → RefereesController@index   (referees.index)
GET    /manage/referees/create         → RefereesController@create  (referees.create)
POST   /manage/referees                → RefereesController@store   (referees.store)
GET    /manage/referees/{referee}/edit → RefereesController@edit    (referees.edit)
PUT    /manage/referees/{referee}      → RefereesController@update  (referees.update)
DELETE /manage/referees/{referee}      → RefereesController@destroy (referees.destroy)
```

All routes: `auth` + `verified` middleware. No `show` route.

---

## Service binding

```php
$this->app->bind(RefereeInterface::class, fn () => new RefereeRepository(new Referee));
```

---

## Related

- [RefereesController](RefereesController.md)
- [RefereeRepository](RefereeRepository.md)
- [System/Referee](../../System/Referee.md)
