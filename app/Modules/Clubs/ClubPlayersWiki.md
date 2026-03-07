# Club Players — Wiki

## Overview

Club-Player membership management lives as a sub-feature of the Clubs module. It allows a club manager to:

- **Assign** an existing player to a club (creates a `clubs_players` record with a `started_at` date)
- **End** a player's membership (sets `ended_at` on the `clubs_players` record)
- **View** all memberships for a club — both active and historical

Membership records are **never hard-deleted**. History is always preserved. A null `ended_at` means the membership is currently active.

---

## Database Table

### `clubs_players`

Tracks club membership history. A player may belong to multiple clubs over time and may rejoin the same club after leaving.

| Column     | Type      | Notes                                                          |
|------------|-----------|----------------------------------------------------------------|
| id         | ulid (PK) |                                                                |
| club_id    | ulid (FK) | → clubs.id ON DELETE RESTRICT ON UPDATE CASCADE                |
| player_id  | ulid (FK) | → players.id ON DELETE RESTRICT ON UPDATE CASCADE              |
| started_at  | datetime       | When the player joined this club                               |
| ended_at    | datetime       | Nullable — null means membership is currently active           |
| belt_rank   | tinyint        | Nullable — `BeltRankEnum` integer value (0=White … 7=Black)    |
| grade       | varchar        | Nullable — free-form sub-grade, e.g. `1st dan`, `2nd kyu`      |
| *audit*     |                | EditorTrait                                                    |

**Index**: `(club_id, player_id)` for fast membership lookups.

**FK behaviour**: Both `club_id` and `player_id` are RESTRICT. Neither a club nor a player can be hard-deleted while membership rows reference them. Since clubs and players use `SoftDeletes`, their deletes are UPDATEs (setting `deleted_at`) — the RESTRICT constraint is not triggered.

**Business rules** (enforced at application level):
- A player must not have two active memberships (`ended_at IS NULL`) at the same club simultaneously.
- Ending a membership = setting `ended_at` to the provided date (not deleting the row).
- The `{membership}` route parameter is `clubs_players.id` (ULID) — not `player_id` — because a player may have multiple historical records at the same club.

---

## Module Structure

New files added within `app/Modules/Clubs/`:

```
app/
├── System/
│   └── ClubPlayer/
│       ├── Models/
│       │   └── ClubPlayer.php              # Canonical model: fillable, casts, club/player relationships
│       └── Foundation/
│           └── Enums/
│               └── BeltRankEnum.php        # int-backed enum: WHITE=0 … BLACK=7
└── Modules/
    └── Clubs/
        ├── Controllers/
        │   ├── ClubsController.php         # Existing — show() also passes memberships + availablePlayers
        │   └── ClubPlayersController.php   # store (assign), update (end membership) — no index, handled by ClubsController::show
        ├── Interfaces/
        │   ├── ClubInterface.php           # Existing — unchanged
        │   └── ClubPlayerInterface.php     # Declares getClubPlayers, getAvailablePlayers, assignPlayer, endMembership
        ├── Models/
        │   ├── Club.php                    # Existing — unchanged
        │   ├── Address.php                 # Existing — unchanged
        │   └── ClubPlayer.php              # Module-level model (extends System ClubPlayer)
        ├── Repositories/
        │   ├── ClubRepository.php          # Existing — unchanged
        │   └── ClubPlayerRepository.php    # Owns full membership lifecycle
        ├── Requests/
        │   ├── ClubPlayerAssignRequest.php # Validates player_id, started_at
        │   └── ClubPlayerEndRequest.php    # Validates ended_at
        ├── Routes/
        │   └── web.php                     # Existing — add nested clubs.players routes here
        └── ServiceProvider.php             # Existing — add ClubPlayerInterface binding
```

---

## System Model

**`app/System/ClubPlayer/Models/ClubPlayer.php`**

```php
protected $table = 'clubs_players';

protected $fillable = ['club_id', 'player_id', 'started_at', 'ended_at', 'belt_rank', 'grade'];

protected $casts = [
    'started_at' => 'datetime',
    'ended_at'   => 'datetime',
    'belt_rank'  => BeltRankEnum::class,
];

public function club(): BelongsTo
{
    return $this->belongsTo(Club::class, 'club_id', 'id');
}

public function player(): BelongsTo
{
    return $this->belongsTo(Player::class, 'player_id', 'id');
}
```

The module-level `app/Modules/Clubs/Models/ClubPlayer.php` extends this system model (thin wrapper, starts empty).

---

## Enums

### `app/System/ClubPlayer/Foundation/Enums/BeltRankEnum.php`

```php
enum BeltRankEnum: int
{
    case WHITE  = 0;
    case YELLOW = 1;
    case ORANGE = 2;
    case GREEN  = 3;
    case BLUE   = 4;
    case PURPLE = 5;
    case BROWN  = 6;
    case BLACK  = 7;
}
```

Stored as a `tinyint` in the database. Nullable — a membership record does not require a belt rank.

---

## Relationships to Add

### On `app/System/Club/Models/Club.php`

```php
public function memberships(): HasMany
{
    return $this->hasMany(ClubPlayer::class, 'club_id');
}

public function activeMemberships(): HasMany
{
    return $this->hasMany(ClubPlayer::class, 'club_id')->whereNull('ended_at');
}

public function players(): BelongsToMany
{
    return $this->belongsToMany(Player::class, 'clubs_players', 'club_id', 'player_id')
        ->withPivot(['id', 'started_at', 'ended_at']);
}
```

### On `app/System/Player/Models/Player.php`

```php
public function clubs(): BelongsToMany
{
    return $this->belongsToMany(Club::class, 'clubs_players', 'player_id', 'club_id')
        ->withPivot(['id', 'started_at', 'ended_at']);
}
```

---

## ClubPlayerInterface

```php
interface ClubPlayerInterface extends BaseInterface
{
    /** Paginated list of all memberships (active + historical) for the given club. */
    public function getClubPlayers(string $clubId, array $filters): LengthAwarePaginator;

    /** All players NOT currently active members of the club — for the assign dropdown. */
    public function getAvailablePlayers(string $clubId): Collection;

    /** Create a new membership record after checking for duplicate active memberships. */
    public function assignPlayer(string $clubId, array $validated): ClubPlayer;

    /** Set ended_at on the membership to end it. */
    public function endMembership(string $clubId, string $membershipId, array $validated): ClubPlayer;
}
```

---

## Repository

**`ClubPlayerRepository`** injects both the `ClubPlayer` model and the `Player` model.

```php
public function __construct(ClubPlayer $model, private Player $player)
{
    parent::__construct($model);
}
```

### Key method implementations

**`getClubPlayers`** — eager-loads the `player` relationship; supports filtering by `active` (ended_at IS NULL) or all:

```php
public function getClubPlayers(string $clubId, array $filters): LengthAwarePaginator
{
    $query = $this->model->newQuery()
        ->with('player')
        ->where('club_id', $clubId);

    if (($filters['status'] ?? 'all') === 'active') {
        $query->whereNull('ended_at');
    }

    return $query->orderBy('started_at', 'desc')->paginate($filters['per_page'] ?? 15);
}
```

**`getAvailablePlayers`** — excludes players with an active membership at this club:

```php
public function getAvailablePlayers(string $clubId): Collection
{
    $activeMemberIds = $this->model->newQuery()
        ->where('club_id', $clubId)
        ->whereNull('ended_at')
        ->pluck('player_id');

    return $this->player->newQuery()
        ->whereNotIn('id', $activeMemberIds)
        ->orderBy('name')
        ->get();
}
```

**`assignPlayer`** — guards against duplicate active memberships:

```php
public function assignPlayer(string $clubId, array $validated): ClubPlayer
{
    $alreadyActive = $this->model->newQuery()
        ->where('club_id', $clubId)
        ->where('player_id', $validated['player_id'])
        ->whereNull('ended_at')
        ->exists();

    if ($alreadyActive) {
        throw new \RuntimeException('Player already has an active membership at this club.');
    }

    return $this->model->newQuery()->create([
        'club_id'    => $clubId,
        'player_id'  => $validated['player_id'],
        'started_at' => $validated['started_at'],
        'belt_rank'  => $validated['belt_rank'] ?? null,
        'grade'      => $validated['grade'] ?? null,
    ]);
}
```

**`endMembership`** — sets `ended_at`:

```php
public function endMembership(string $clubId, string $membershipId, array $validated): ClubPlayer
{
    $membership = $this->model->newQuery()
        ->where('club_id', $clubId)
        ->findOrFail($membershipId);

    $membership->update(['ended_at' => $validated['ended_at']]);

    return $membership;
}
```

---

## Controller

**`ClubPlayersController`** — thin, delegates entirely to repository. Only handles write operations; the list view is rendered by `ClubsController::show()`.

```php
public function __construct(private readonly ClubPlayerInterface $repository) {}

public function store(ClubPlayerAssignRequest $request, string $club): RedirectResponse
{
    $this->repository->assignPlayer($club, $request->validated());

    return to_route('clubs.show', $club)->with('success', 'Player assigned successfully.');
}

public function update(ClubPlayerEndRequest $request, string $club, string $membership): RedirectResponse
{
    $this->repository->endMembership($club, $membership, $request->validated());

    return to_route('clubs.show', $club)->with('success', 'Membership ended successfully.');
}
```

**`ClubsController::show()`** — extended to also pass membership data for the detail page:

```php
public function show(string $club): Response
{
    return $this->render('clubs/show', [
        'club'             => $this->repository->getById($club),
        'memberships'      => $this->clubPlayers->getClubPlayers($club, []),
        'availablePlayers' => $this->clubPlayers->getAvailablePlayers($club),
    ]);
}
```

---

## Validation Rules

### `ClubPlayerAssignRequest`

| Field      | Rules                                                                              |
|------------|------------------------------------------------------------------------------------|
| player_id  | required, string, exists:players,id                                                |
| started_at | required, date_format:Y-m-d, before_or_equal:today                                |
| belt_rank  | nullable, integer, in: BeltRankEnum values (0–7)                                   |
| grade      | nullable, string, max:50                                                           |

### `ClubPlayerEndRequest`

| Field    | Rules                                                               |
|----------|---------------------------------------------------------------------|
| ended_at | required, date_format:Y-m-d, after_or_equal:started_at (of membership) |

> `ClubPlayerEndRequest` must load the membership record to validate `after_or_equal` against the specific `started_at`. Use a custom `after` rule referencing the `started_at` of the resolved membership model.

---

## Routes

Added to `app/Modules/Clubs/Routes/web.php` as nested routes under the clubs group:

```php
Route::group([
    'middleware' => ['auth', 'verified'],
    'prefix'     => 'manage',
], function () {
    // Existing clubs routes
    Route::resource('clubs', 'ClubsController')->names('clubs');
    Route::post('clubs/{club}', [ClubsController::class, 'update'])->name('clubs.update');

    // Club Players (write operations only — list is served by clubs.show)
    Route::prefix('clubs/{club}/players')->name('clubs.players.')->group(function () {
        Route::post('/',            [ClubPlayersController::class, 'store']) ->name('store');
        Route::put('/{membership}', [ClubPlayersController::class, 'update'])->name('update');
    });
});
```

Named routes:

```
GET  /manage/clubs/{club}                        → clubs.show  (detail + members list)
POST /manage/clubs/{club}/players                → clubs.players.store
PUT  /manage/clubs/{club}/players/{membership}   → clubs.players.update
```

---

## Frontend Pages

| File                                         | Purpose                                                                       |
|----------------------------------------------|-------------------------------------------------------------------------------|
| `pages/clubs/show.tsx`                       | Club detail card + members table; "Assign Player" dialog; "End Membership" dialog |
| `pages/clubs/players/partials/columns.tsx`   | TanStack Table column definitions for membership rows                         |

### Page behaviour

- **Club detail** (`GET /manage/clubs/{club}`) — shows club info card (logo, name, abbreviation, address, phone) and the full members table below it.
- **Assign Player** — opens a Dialog with a `<Select>` populated from `availablePlayers`. Fields: player (required), started_at (defaults to today), belt_rank (optional), grade (optional). Submits via Inertia `POST clubs.players.store`, redirects back to `clubs.show`.
- **End Membership** — per active membership row, an "End Membership" button opens a Dialog with a date picker for `ended_at`. Submits via Inertia `PUT clubs.players.update`, redirects back to `clubs.show`.
- Historical memberships (ended_at set) are shown with a muted "Ended" badge; no action available on them.
- The page includes a breadcrumb trail: `Clubs → {club.name}`.
- Dialogs do not close on outside click (`onInteractOutside` prevented) so the Calendar Popover inside a Dialog works correctly.

---

## TypeScript Types

```typescript
export type BeltRank = 0 | 1 | 2 | 3 | 4 | 5 | 6 | 7;  // WHITE … BLACK

export const BELT_RANK_LABELS: Record<BeltRank, string> = {
    0: 'White',
    1: 'Yellow',
    2: 'Orange',
    3: 'Green',
    4: 'Blue',
    5: 'Purple',
    6: 'Brown',
    7: 'Black',
};

export type ClubMembership = {
    id: string;
    club_id: string;
    player_id: string;
    player: {
        id: string;
        name: string;
        gender: PlayerGender;
        dob: string;
        weight: number;
        profile_photo_url: string | null;
    };
    started_at: string;
    ended_at: string | null;   // null = currently active
    belt_rank: BeltRank | null;
    grade: string | null;
    created_at: string;
    updated_at: string;
};
```

### Props shape for `clubs/players/index.tsx`

```typescript
type ClubPlayersProps = {
    club: Club;
    memberships: {
        data: ClubMembership[];
        links: PaginationLink[];
    };
    availablePlayers: Pick<Player, 'id' | 'name'>[];
    filters: PageFilters;
};
```

---

## Factory

**`ClubPlayerFactory`** — creates a `clubs_players` row with `started_at` in the past and `ended_at` nullable:

```php
public function definition(): array
{
    return [
        'club_id'    => Club::factory(),
        'player_id'  => Player::factory(),
        'started_at' => fake()->dateTimeBetween('-3 years', '-1 month'),
        'ended_at'   => null,
        'belt_rank'  => fake()->optional()->randomElement(BeltRankEnum::cases())->value,
        'grade'      => null,
    ];
}

/** State: membership already ended. */
public function ended(): static
{
    return $this->state(fn (array $attributes) => [
        'ended_at' => fake()->dateTimeBetween($attributes['started_at'], 'now'),
    ]);
}
```

---

## ServiceProvider Binding

Add to `app/Modules/Clubs/ServiceProvider.php`:

```php
$this->app->bind(ClubPlayerInterface::class, ClubPlayerRepository::class);
```