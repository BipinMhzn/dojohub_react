# Coaches Module

**Directory:** `app/Modules/Coaches/`  
**Routes prefix:** `/manage/coaches`  
**Route names prefix:** `coaches`

Manages coach records. Coaches are standalone entities that can be linked to clubs via the `ClubCoaches` sub-module within Clubs. No `show` route — list and edit are the primary views.

---

## Controller

| Controller | Route prefix | Inertia pages |
|-----------|-------------|---------------|
| `CoachesController` | `/manage/coaches` | `coaches/index`, `coaches/create`, `coaches/edit` |

---

## Routes

```
GET    /manage/coaches               → CoachesController@index   (coaches.index)
GET    /manage/coaches/create        → CoachesController@create  (coaches.create)
POST   /manage/coaches               → CoachesController@store   (coaches.store)
GET    /manage/coaches/{coach}/edit  → CoachesController@edit    (coaches.edit)
PUT    /manage/coaches/{coach}       → CoachesController@update  (coaches.update)
DELETE /manage/coaches/{coach}       → CoachesController@destroy (coaches.destroy)
```

Note: No `show` route (resource excludes it). Uses standard PUT for update (no file upload).

---

## Service binding

```php
$this->app->bind(CoachInterface::class, fn () => new CoachRepository(new Coach));
```

---

## Related

- [CoachesController](CoachesController.md)
- [CoachRepository](CoachRepository.md)
- [System/Coach](../../System/Coach.md)
