# Venues Module — Wiki

## Overview

The Venues module manages physical locations where tournaments are held. A venue always has a required physical address stored in the shared `addresses` table. The number of available tatami mats is tracked per venue for match scheduling.

---

## Database Tables

### `addresses`

Shared address table used by both `venues` and `clubs`.

| Column  | Type        | Notes               |
|---------|-------------|---------------------|
| id      | ulid (PK)   |                     |
| address | string      | Street address line |
| city    | string(100) |                     |
| state   | string(100) |                     |
| country | string(100) |                     |
| *audit* |             | EditorTrait         |

### `venues`

| Column     | Type      | Notes                                                              |
|------------|-----------|--------------------------------------------------------------------|
| id         | ulid (PK) |                                                                    |
| name       | string    | Venue display name                                                 |
| address_id | ulid (FK) | → addresses.id (RESTRICT on delete, CASCADE on update)            |
| mat_count  | tinyint   | Number of tatami mats available. Default 1. Used for scheduling.  |
| *audit*    |           | EditorTrait                                                        |

**FK behaviour**: `address_id` is RESTRICT — an address cannot be hard-deleted while a venue references it. Since both models use `SoftDeletes`, the delete operations are UPDATEs (setting `deleted_at`), which are not blocked by the RESTRICT constraint.

---

## Module Structure

```
app/
├── System/
│   ├── Address/
│   │   └── Models/
│   │       └── Address.php              # Canonical address model (shared by venues + clubs)
│   └── Venue/
│       └── Models/
│           └── Venue.php                # Canonical venue model: fillable, address() relationship
└── Modules/
    └── Venues/
        ├── Controllers/
        │   └── VenuesController.php     # Thin — delegates to repository custom methods
        ├── Filters/
        │   └── VenuesFilter.php         # keyword → searches by name
        ├── Interfaces/
        │   └── VenueInterface.php       # Declares createVenue, updateVenue, deleteVenue
        ├── Models/
        │   ├── Address.php              # Module-level Address (extends System Address)
        │   └── Venue.php                # Module-level Venue (extends System Venue)
        ├── Repositories/
        │   └── VenueRepository.php      # Owns address + venue lifecycle; Address injected
        ├── Requests/
        │   ├── VenueCreateRequest.php   # Validates name, mat_count, address fields
        │   └── VenueUpdateRequest.php   # Extends VenueCreateRequest
        ├── Routes/
        │   ├── api.php                  # Empty
        │   └── web.php                  # Resource routes (auth+verified, manage prefix)
        └── ServiceProvider.php          # Binds VenueInterface → VenueRepository
```

---

## Key Design Pattern

### Repository Owns the Address Lifecycle

All address + venue logic lives in the repository. The controller is thin — it only calls repository methods and redirects.

**Repository constructor injects both models:**

```php
public function __construct(Venue $model, private Address $address)
{
    parent::__construct($model);
}
```

**Custom repository methods (declared in VenueInterface):**

| Method | Action |
|--------|--------|
| `createVenue(array $validated): Venue` | Creates Address record, then Venue with address_id |
| `updateVenue(array $validated, string $id): Venue` | Updates address via relationship, then updates venue |
| `deleteVenue(string $id): true` | Soft-deletes address, then soft-deletes venue |

**Delete flow (soft-delete safe with RESTRICT FK):**

```php
// SoftDeletes issues UPDATE (sets deleted_at), not DELETE — RESTRICT is not triggered
$venueModel->address?->delete();   // soft-delete address
$venueModel->delete();             // soft-delete venue
```

### Eager Loading

`VenueRepository` overrides `getById` and implements `onBeforeResult` to always eager-load the `address` relationship — no N+1 queries.

```php
public function onBeforeResult(Builder $builder): void
{
    $builder->with('address');
}

public function getById(string $id, array $attributes = []): Venue
{
    return $this->model->newQuery()->with('address')->findOrFail($id);
}
```

---

## System Models

**`app/System/Venue/Models/Venue.php`**

```php
protected $fillable = ['name', 'address_id', 'mat_count'];

public function address(): BelongsTo
{
    return $this->belongsTo(Address::class, 'address_id', 'id');
}
```

**`app/System/Address/Models/Address.php`**

```php
protected $fillable = ['address', 'city', 'state', 'country'];
```

---

## VenueInterface

```php
interface VenueInterface extends BaseInterface
{
    public function createVenue(array $validated): Venue;
    public function updateVenue(array $validated, string $venueId): Venue;
    public function deleteVenue(string $venueId): true;
}
```

---

## Validation Rules (`VenueCreateRequest`)

| Field     | Rules                              |
|-----------|------------------------------------|
| name      | required, string, max:250          |
| mat_count | required, integer, min:1, max:99   |
| country   | required, string, max:100          |
| state     | required, string, max:100          |
| city      | required, string, max:100          |
| address   | required, string, max:250          |

`VenueUpdateRequest` extends `VenueCreateRequest` with identical rules.

---

## Routes

```
GET    /manage/venues              → venues.index
GET    /manage/venues/create       → venues.create
POST   /manage/venues              → venues.store
GET    /manage/venues/{venue}/edit → venues.edit
PUT    /manage/venues/{venue}      → venues.update
DELETE /manage/venues/{venue}      → venues.destroy
```

Middleware: `auth`, `verified`. `show` route is excluded.

---

## Frontend Pages

| File                               | Purpose                                   |
|------------------------------------|-------------------------------------------|
| `pages/venues/index.tsx`           | Paginated list with search/sort controls  |
| `pages/venues/create.tsx`          | Create form                               |
| `pages/venues/edit.tsx`            | Edit form (pre-populated from venue.address.*) |
| `pages/venues/partials/columns.tsx`| TanStack Table column definitions          |

### TypeScript Types

```typescript
export type VenueAddress = {
    id: string;
    address: string;
    city: string;
    state: string;
    country: string;
};

export type Venue = {
    id: string;
    name: string;
    mat_count: number;
    address: VenueAddress;   // nested eager-loaded relationship
    created_at: string;
    updated_at: string;
};
```

### Form Data Shape (flat — repository splits into Address + Venue)

```typescript
{
    name: string;
    mat_count: number;
    address: string;   // street address line
    city: string;
    state: string;
    country: string;
}
```

---

## Factories

| Factory            | Notes                                            |
|--------------------|--------------------------------------------------|
| `AddressFactory`   | Generates random street address, city, state, country |
| `VenueFactory`     | Creates an `Address` via factory first, then creates `Venue` with `address_id` |
