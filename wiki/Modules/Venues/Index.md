# Venues Module

**Directory:** `app/Modules/Venues/`  
**Routes prefix:** `/manage/venues`  
**Route names prefix:** `venues`

Manages competition venues. Each venue has an address and a set of competition mats. Venues are referenced by tournaments.

---

## Controllers

| Controller | Route prefix | Inertia pages |
|-----------|-------------|---------------|
| `VenuesController` | `/manage/venues` | `venues/index`, `venues/create`, `venues/show` |
| `VenueMatController` | `/manage/venues/{venue}/mats` | `venues/mats/index` |

---

## Routes

```
GET    /manage/venues                  → VenuesController@index         (venues.index)
GET    /manage/venues/create           → VenuesController@create        (venues.create)
POST   /manage/venues                  → VenuesController@store         (venues.store)
GET    /manage/venues/{venue}          → VenuesController@show          (venues.show)
GET    /manage/venues/{venue}/edit     → VenuesController@edit          (venues.edit) *redirect to show
PUT    /manage/venues/{venue}          → VenuesController@update        (venues.update)
DELETE /manage/venues/{venue}          → VenuesController@destroy       (venues.destroy)

GET    /manage/venues/{venue}/mats     → VenueMatController@index       (venues.mats.index)
POST   /manage/venues/{venue}/mats     → VenueMatController@store       (venues.mats.store)
PUT    /manage/venues/{venue}/mats/{mat} → VenueMatController@update    (venues.mats.update)
DELETE /manage/venues/{venue}/mats/{mat} → VenueMatController@destroy   (venues.mats.destroy)
```

All routes: `auth` + `verified` middleware.

---

## Database tables

| Table | Model | Notes |
|-------|-------|-------|
| `venues` | `Venue` | Main venue record |
| `addresses` | `Address` | Venue address (FK `address_id`) |
| `venue_mats` | `VenueMat` | Mats for this venue |

---

## Service bindings (`ServiceProvider.php`)

```php
$this->app->bind(VenueInterface::class, fn () => new VenueRepository(
    new Venue,
    new Address,
));

$this->app->bind(VenueMatInterface::class, fn () => new VenueMatRepository(
    new VenueMat,
));
```

Also registers `VenueEventSubscriber`.

---

## Module files

| File | Purpose |
|------|---------|
| `Controllers/VenuesController.php` | CRUD for venues |
| `Controllers/VenueMatController.php` | CRUD for mats within a venue |
| `Repositories/VenueRepository.php` | Venue data access + address transactions |
| `Repositories/VenueMatRepository.php` | Mat data access |
| `Filters/VenuesFilter.php` | `keyword` filter (delegates to `name`) |
| `Requests/VenueCreateRequest.php` | Create validation rules |
| `Requests/VenueUpdateRequest.php` | Extends create (same rules) |
| `Requests/VenueMatRequest.php` | Mat create/update rules |
| `Interfaces/VenueInterface.php` | Repository contract |
| `Interfaces/VenueMatInterface.php` | Mat repository contract |
| `Exceptions/VenueInUseException.php` | Thrown when deleting a venue in use |
| `Exceptions/VenueMatInUseException.php` | Thrown when deleting a mat in use |
| `Listeners/VenueEventSubscriber.php` | Domain event subscriber |
| `ServiceProvider.php` | Binds interfaces, registers subscriber |

---

## VenueEventSubscriber — Delete Guard

**File:** `app/Modules/Venues/Listeners/VenueEventSubscriber.php`

Registered in `ServiceProvider.php` via `$events->subscribe(VenueEventSubscriber::class)`.

Hooks into Eloquent model events to prevent deleting venues or mats that are still in active use:

```php
// eloquent.deleting: Venue
public function handleVenueDeleting(Venue $venue): void
{
    if (Tournament::where('venue_id', $venue->id)->exists()) {
        throw new VenueInUseException(
            "Cannot delete \"{$venue->name}\" because it is assigned to one or more tournaments."
        );
    }
}

// eloquent.deleting: VenueMat
public function handleVenueMatDeleting(VenueMat $mat): void
{
    if (TournamentMatch::where('mat_id', $mat->id)->exists()) {
        throw new VenueMatInUseException(
            'Cannot remove this mat because it is assigned to one or more tournament matches.'
        );
    }
}
```

**Why this fires**: Eloquent raises `eloquent.deleting` immediately before executing the `UPDATE … SET deleted_at = now()` statement. The subscriber runs synchronously — if an exception is thrown, the DELETE is aborted and the transaction rolls back.

**Caught by the controller** (`VenuesController@destroy` / `VenueMatController@destroy`):
```php
} catch (VenueInUseException $e) {
    return redirect()->back()->with('error', $e->getMessage());
}
```

This produces a flash `error` toast on the frontend rather than a 500 error.

---

## Related

- [VenuesController](VenuesController.md)
- [VenueMatController](VenueMatController.md)
- [VenueRepository](VenueRepository.md)
- [System/Venue](../../System/Venue.md)
- [System/VenueMat](../../System/VenueMat.md)
