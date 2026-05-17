# Players Module — Wiki

## Overview

The Players module manages individual athletes in the system. A player has required fields (name, gender, DOB, weight) and optional fields (nationality, email, phone number, profile photo). Players are linked to clubs via the `clubs_players` pivot table and can be enrolled in tournament categories via `tournament_registrations`.

---

## Database Table

### `players`

| Column            | Type         | Notes                                                            |
|-------------------|--------------|------------------------------------------------------------------|
| id                | ulid (PK)    |                                                                  |
| name              | string       |                                                                  |
| gender            | tinyint      | See [Gender Enum](#gender-enum). 0 = Male, 1 = Female            |
| dob               | date         | Date of birth                                                    |
| weight            | decimal(5,2) | Current weight in kg (snapshot also taken at registration time)  |
| nationality       | string(100)  | Nullable — country the player competes for                       |
| email             | string       | Nullable — contact email                                         |
| profile_photo_url | string       | Nullable — path to uploaded profile photo                        |
| phone_number      | string(20)   | Nullable                                                         |
| *audit*           |              | EditorTrait                                                      |

---

## Module Structure

```
app/
├── System/
│   └── Player/
│       ├── Foundation/
│       │   └── Enums/
│       │       └── PlayerGenderEnum.php     # int-backed enum: MALE=0, FEMALE=1
│       └── Models/
│           └── Player.php                  # Canonical player model: fillable, casts, sortableColumns
└── Modules/
    └── Players/
        ├── Controllers/
        │   └── PlayersController.php       # Handles file uploads; delegates to repository
        ├── Filters/
        │   └── PlayersFilter.php           # keyword → searches by name
        ├── Interfaces/
        │   └── PlayerInterface.php         # Extends BaseInterface (no custom methods)
        ├── Models/
        │   └── Player.php                  # Module-level Player (extends System Player)
        ├── Repositories/
        │   └── PlayerRepository.php        # Standard BaseRepository; no custom lifecycle
        ├── Requests/
        │   ├── PlayerCreateRequest.php     # Validates all player fields
        │   └── PlayerUpdateRequest.php     # Extends PlayerCreateRequest
        ├── Routes/
        │   ├── api.php                     # Empty
        │   └── web.php                     # Resource routes + POST for update (file uploads)
        └── ServiceProvider.php             # Binds PlayerInterface → PlayerRepository
```

---

## Key Design Pattern

### Standard Repository (no related model)

Unlike Venues/Clubs, the Player model has no nested related entity. The controller uses `BaseRepository` methods directly (`create`, `update`, `destroy`, `getById`, `getList`).

```php
// Controller delegates directly to BaseRepository methods
$this->repository->create($inputs);
$this->repository->update($player, $inputs);
$this->repository->destroy($player);
```

### File Upload Handling

The controller uses the `FileUpload` trait. Profile photo upload is optional on both create and update.

```php
protected string $uploadDirectory = '/images/players';

// In store/update:
if ($request->file('profile_photo')) {
    $profilePhoto = $this->uploadFile($request->file('profile_photo'), '/images/players');
    $inputs['profile_photo_url'] = $profilePhoto['path'];
    unset($inputs['profile_photo']);
}
```

Accepted formats: jpeg, jpg, png, gif, svg (max 5 MB). Stored path written to `profile_photo_url`.

---

## System Model

**`app/System/Player/Models/Player.php`**

```php
protected $fillable = [
    'name',
    'gender',
    'dob',
    'weight',
    'nationality',
    'email',
    'profile_photo_url',
    'phone_number',
];

protected array $sortableColumns = ['name', 'created_at', 'updated_at'];

protected $casts = [
    'gender' => PlayerGenderEnum::class,
];
```

---

## Gender Enum

**`app/System/Player/Foundation/Enums/PlayerGenderEnum.php`**

| Case   | Value |
|--------|-------|
| MALE   | 0     |
| FEMALE | 1     |

> Used in: `players.gender` (stored as tinyint, cast to enum on model)

---

## Validation Rules (`PlayerCreateRequest`)

| Field         | Rules                                                                |
|---------------|----------------------------------------------------------------------|
| name          | required, string, max:255                                            |
| gender        | required, Enum(PlayerGenderEnum)                                     |
| dob           | required, date_format:Y-m-d, before_or_equal:today                  |
| weight        | required, numeric, min:1, max:500                                    |
| nationality   | nullable, string, max:100                                            |
| email         | nullable, email, max:255                                             |
| profile_photo | nullable, mimes:jpeg,jpg,png,svg,gif, max:5120 (5 MB)               |
| phone_number  | nullable, max:20, regex phone format                                 |

`PlayerUpdateRequest` extends `PlayerCreateRequest` with identical rules.

---

## Routes

```
GET    /manage/players              → players.index
GET    /manage/players/create       → players.create
POST   /manage/players              → players.store
GET    /manage/players/{player}/edit → players.edit
POST   /manage/players/{player}     → players.update   (POST, not PUT — file upload support)
DELETE /manage/players/{player}     → players.destroy
GET    /manage/players/{player}     → players.show
```

Middleware: `auth`, `verified`.

> **Note**: Update uses POST (not PUT) because multipart file uploads require POST. Same pattern as the Clubs module.

---

## Frontend Pages

| File                               | Purpose                                          |
|------------------------------------|--------------------------------------------------|
| `pages/players/index.tsx`          | Paginated list with search/sort controls         |
| `pages/players/create.tsx`         | Create form                                      |
| `pages/players/edit.tsx`           | Edit form (pre-populated from player fields)     |
| `pages/players/partials/columns.tsx` | TanStack Table column definitions               |

### TypeScript Types

```typescript
export enum PlayerGender {
    MALE = 0,
    FEMALE = 1,
}

export type Player = {
    id: string;
    name: string;
    gender: PlayerGender;
    dob: string;
    weight: number;
    nationality: string | null;
    email: string | null;
    profile_photo_url: string | null;
    phone_number: string | null;
    created_at: string;
    updated_at: string;
};
```

### Form Data Shape

```typescript
{
    name: string;
    gender: PlayerGender;
    dob: string;           // yyyy-MM-dd format
    weight: string;        // numeric string (useForm limitation)
    nationality: string;   // empty string when not set
    email: string;         // empty string when not set
    phone_number: string;  // empty string when not set
    profile_photo: File | null;
}
```

### Table Columns

| Column       | Notes                                          |
|--------------|------------------------------------------------|
| Name         | Avatar + name (profile_photo_url as AvatarImage) |
| Gender       | Derived from PlayerGender enum                 |
| Age          | Calculated from dob at render time             |
| Weight (Kg)  | Raw decimal value                              |
| Phone Number | Rendered as `<a href="tel:...">` link          |
| Last Updated | Formatted via `formatUTCDate` utility          |

### Date Picker

DOB uses a shadcn/ui Calendar inside a Popover. The selected Date is formatted to `yyyy-MM-dd` string before being stored in form data.

---

## Factory

| Factory         | Notes                                                                          |
|-----------------|--------------------------------------------------------------------------------|
| `PlayerFactory` | Generates name, gender (random enum), dob, weight, optional nationality/email/phone |

---

## Relationships (downstream)

| Relationship         | Table                    | Notes                                  |
|----------------------|--------------------------|----------------------------------------|
| Club memberships     | `clubs_players`          | Via `club_id` + `player_id` pivot      |
| Tournament registrations | `tournament_registrations` | `player_id` FK — one record per category |
| Match winner         | `tournament_matches`     | `winner_id` → players.id (set null)    |
