# Clubs Module — Wiki

## Overview

The Clubs module manages martial arts organizations that group players. A club has a required physical address stored in the shared `addresses` table, optional contact info (phone number), an abbreviation, and an optional logo. Clubs can be associated with multiple players via the `clubs_players` pivot table.

---

## Database Tables

### `addresses`

Shared address table used by both `clubs` and `venues`.

| Column  | Type        | Notes               |
|---------|-------------|---------------------|
| id      | ulid (PK)   |                     |
| address | string      | Street address line |
| city    | string(100) |                     |
| state   | string(100) |                     |
| country | string(100) |                     |
| *audit* |             | EditorTrait         |

### `clubs`

| Column       | Type      | Notes                                                           |
|--------------|-----------|-----------------------------------------------------------------|
| id           | ulid (PK) |                                                                 |
| name         | string    | Club display name                                               |
| abbreviation | string(10)| Short uppercase identifier (e.g. "KDJ")                        |
| logo_url     | string    | Nullable. Path to uploaded logo image                           |
| address_id   | ulid (FK) | → addresses.id (RESTRICT on delete, CASCADE on update)         |
| phone_number | string(20)| Nullable. Club contact phone number                             |
| *audit*      |           | EditorTrait                                                     |

**FK behaviour**: `address_id` is RESTRICT — an address cannot be hard-deleted while a club references it. Since both models use `SoftDeletes`, delete operations are UPDATEs (setting `deleted_at`), which are not blocked by the RESTRICT constraint.

### `clubs_players`

Pivot table tracking club membership history.

| Column     | Type      | Notes                                                  |
|------------|-----------|--------------------------------------------------------|
| id         | ulid (PK) |                                                        |
| club_id    | ulid (FK) | → clubs.id (RESTRICT on delete, CASCADE on update)     |
| player_id  | ulid (FK) | → players.id (RESTRICT on delete, CASCADE on update)   |
| started_at | datetime  | Membership start date                                  |
| ended_at   | datetime  | Nullable. Membership end date                          |
| *audit*    |           | EditorTrait                                            |

Index on `(club_id, player_id)`.

---

## Module Structure

```
app/
├── System/
│   ├── Address/
│   │   └── Models/
│   │       └── Address.php              # Canonical address model (shared by clubs + venues)
│   └── Club/
│       └── Models/
│           └── Club.php                 # Canonical club model: fillable, address() relationship
└── Modules/
    └── Clubs/
        ├── Controllers/
        │   └── ClubsController.php      # Handles file uploads; delegates to repository
        ├── Filters/
        │   └── ClubsFilter.php          # keyword → searches by name
        ├── Interfaces/
        │   └── ClubInterface.php        # Declares createClub, updateClub, deleteClub
        ├── Models/
        │   ├── Address.php              # Module-level Address (extends System Address)
        │   └── Club.php                 # Module-level Club (extends System Club)
        ├── Repositories/
        │   └── ClubRepository.php       # Owns address + club lifecycle; Address injected
        ├── Requests/
        │   ├── ClubCreateRequest.php    # Validates name, abbreviation, logo, phone, address fields
        │   └── ClubUpdateRequest.php    # Extends ClubCreateRequest
        ├── Routes/
        │   ├── api.php                  # Empty
        │   └── web.php                  # Resource routes (auth+verified, manage prefix)
        └── ServiceProvider.php          # Binds ClubInterface → ClubRepository
```

---

## Key Design Pattern

### Repository Owns the Address Lifecycle

All address + club logic lives in the repository. The controller handles file uploads, then delegates to repository methods.

**Repository constructor injects both models:**

```php
public function __construct(Club $model, private Address $address)
{
    parent::__construct($model);
}
```

**Custom repository methods (declared in ClubInterface):**

| Method | Action |
|--------|--------|
| `createClub(array $validated): Club` | Creates Address record, then Club with address_id |
| `updateClub(array $validated, string $id): Club` | Updates address via relationship, then updates club |
| `deleteClub(string $id): true` | Soft-deletes address, then soft-deletes club |

**Delete flow (soft-delete safe with RESTRICT FK):**

```php
// SoftDeletes issues UPDATE (sets deleted_at), not DELETE — RESTRICT is not triggered
$clubModel->address?->delete();   // soft-delete address
$clubModel->delete();             // soft-delete club
```

### File Upload Handling

The controller uses the `FileUpload` trait. Logo upload is optional on both create and update.

```php
// Upload directory
protected string $uploadDirectory = '/images/clubs';

// In store/update:
if ($request->hasFile('logo')) {
    $validated['logo_url'] = $this->uploadFile($request->file('logo'));
}
unset($validated['logo']);  // remove file from array before repository call
```

Accepted formats: jpeg, jpg, png, gif, svg (max 5 MB). Stored path written to `logo_url`.

### Eager Loading

`ClubRepository` overrides `getById` and implements `onBeforeResult` to always eager-load the `address` relationship — no N+1 queries.

```php
public function onBeforeResult(Builder $builder): void
{
    $builder->with('address');
}

public function getById(string $id, array $attributes = []): Club
{
    return $this->model->newQuery()->with('address')->findOrFail($id);
}
```

---

## System Models

**`app/System/Club/Models/Club.php`**

```php
protected $fillable = ['name', 'abbreviation', 'logo_url', 'address_id', 'phone_number'];

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

## ClubInterface

```php
interface ClubInterface extends BaseInterface
{
    public function createClub(array $validated): Club;
    public function updateClub(array $validated, string $clubId): Club;
    public function deleteClub(string $clubId): true;
}
```

---

## Validation Rules (`ClubCreateRequest`)

| Field        | Rules                                                              |
|--------------|--------------------------------------------------------------------|
| name         | required, string, max:255                                          |
| abbreviation | required, string, uppercase letters only (regex), max:10          |
| logo         | nullable, file, mimes:jpeg,jpg,png,gif,svg, max:5120 (5 MB)       |
| phone_number | nullable, string, max:20, regex phone format                       |
| country      | required, string, max:100                                          |
| state        | required, string, max:100                                          |
| city         | required, string, max:100                                          |
| address      | required, string, max:255                                          |

`ClubUpdateRequest` extends `ClubCreateRequest` with identical rules.

---

## Routes

```
GET    /manage/clubs              → clubs.index
GET    /manage/clubs/create       → clubs.create
POST   /manage/clubs              → clubs.store
GET    /manage/clubs/{club}/edit  → clubs.edit
POST   /manage/clubs/{club}       → clubs.update   (POST, not PUT — file upload support)
DELETE /manage/clubs/{club}       → clubs.destroy
GET    /manage/clubs/{club}       → clubs.show
```

Middleware: `auth`, `verified`.

> **Note**: Update uses POST (not PUT) because multipart file uploads require POST. This differs from the Venues module which uses PUT (no file uploads).

---

## Frontend Pages

| File                              | Purpose                                       |
|-----------------------------------|-----------------------------------------------|
| `pages/clubs/index.tsx`           | Paginated list with search/sort controls      |
| `pages/clubs/create.tsx`          | Create form with cascading country/state/city |
| `pages/clubs/edit.tsx`            | Edit form (pre-populated from club.address.*) |
| `pages/clubs/partials/columns.tsx`| TanStack Table column definitions              |

### TypeScript Types

```typescript
export type ClubAddress = {
    id: string;
    address: string;
    city: string;
    state: string;
    country: string;
};

export type Club = {
    id: string;
    name: string;
    abbreviation: string;
    logo_url: string | null;
    address: ClubAddress;   // nested eager-loaded relationship
    phone_number: string | null;
    created_at: string;
    updated_at: string;
};
```

### Form Data Shape (flat — repository splits into Address + Club)

```typescript
{
    name: string;
    abbreviation: string;
    logo?: File;          // optional file upload
    phone_number?: string;
    address: string;      // street address line
    city: string;
    state: string;
    country: string;
}
```

### Cascading Dropdowns

Country → State → City dropdowns cascade on the create and edit forms. Changing the country resets state and city; changing state resets city. The edit form preserves initial values from `club.address.*`.

---

## Factories

| Factory          | Notes                                                                       |
|------------------|-----------------------------------------------------------------------------|
| `AddressFactory` | Generates random street address, city, state, country                       |
| `ClubFactory`    | Creates an `Address` via factory first, then creates `Club` with `address_id`; logo_url nullable |
