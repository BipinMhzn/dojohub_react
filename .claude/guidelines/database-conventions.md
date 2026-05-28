# Database Conventions Guideline

How to design migrations, models, and database schemas in dojohub_react.

---

## Primary keys

- **All domain tables** use ULID primary keys: `$table->ulid('id')->primary();`
- **Only `users`** keeps Laravel's default auto-increment `bigint` PK
- Foreign keys are ULIDs: `$table->ulid('venue_id');` then `->foreign(...)`

```php
Schema::create('venues', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->string('name');
    $table->ulid('address_id');
    $this->editorLogs($table);

    $table->foreign('address_id')->references('id')->on('addresses')
        ->onDelete('restrict')->onUpdate('cascade');
});
```

---

## EditorLogs trait — audit columns

Every domain migration uses `Database\Traits\EditorTrait` for the audit columns:

```php
use Database\Traits\EditorTrait;

return new class extends Migration
{
    use EditorTrait;

    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // ... your columns ...
            $this->editorLogs($table);   // adds created_by, updated_by, deleted_by, timestamps, softDeletes
        });
    }
};
```

`editorLogs($table)` adds:
- `created_by`, `updated_by`, `deleted_by` (nullable, FK → `users.id`)
- `created_at`, `updated_at` (Eloquent timestamps)
- `deleted_at` (soft-delete column)

**Don't manually add these columns** — always use the trait.

---

## Foreign keys — `RESTRICT` on parents

Default to `ON DELETE RESTRICT` for parent rows. Reasoning: combined with Eloquent's `SoftDeletes`, this prevents accidental hard-deletes from cascading away child data, while soft-deletes (UPDATE on `deleted_at`) work normally.

```php
$table->foreign('address_id')->references('id')->on('addresses')
    ->onDelete('restrict')->onUpdate('cascade');
```

Use `cascade` only when the child row is a true sub-entity that should disappear with the parent (rare — most relationships use `restrict`).

---

## Migration naming

| Aspect | Convention |
|--------|------------|
| Filename | snake_case + timestamp prefix |
| Timestamp prefix | `YYYY_MM_DD_NNNNNN` (Laravel default for `make:migration`) |
| Verb | `create_<table>_table.php`, `add_<column>_to_<table>_table.php`, `update_…` |
| Table names | plural, snake_case (`venues`, `tournament_categories`, `clubs_players`) |

Examples:
```
2026_01_01_000005_create_venues_table.php
2026_01_01_000009_create_clubs_players_table.php  (junction table — both plural)
```

The early `0001_01_01_…` prefixed migrations are Laravel/Inertia stock (`users`, `cache`, `jobs`, `audits`). Domain migrations start at `2026_01_01_*`.

---

## Enums — stored as integers

Backed PHP enums with `tinyint unsigned` storage in DB.

```php
// Schema
$table->unsignedTinyInteger('status')->default(0);

// PHP enum — int-backed, TitleCase cases
namespace App\System\Player\Foundation\Enums;

enum PlayerGenderEnum: int
{
    case Male = 0;
    case Female = 1;
    case Other = 2;
}

// Model cast
protected function casts(): array
{
    return [
        'gender' => PlayerGenderEnum::class,
    ];
}
```

**Rules:**
- Enums live in `app/System/{Entity}/Foundation/Enums/{Name}Enum.php`
- Int-backed only (not string-backed) — keeps DB column small
- Cases are **TitleCase** (`Pending`, `InProgress`) — never SNAKE_CASE
- Store as `unsignedTinyInteger` unless you need > 255 values
- Cast via the Laravel 12 `casts()` method (NOT the `$casts` property)

Canonical list: [wiki/Enums.md](../../wiki/Enums.md).

---

## Model conventions

### `$fillable` not `$guarded`

Every model declares `protected $fillable = [...]` explicitly. Never use `$guarded = []`.

```php
class Venue extends AppBaseModel
{
    protected $fillable = ['name', 'address_id'];
}
```

### `$sortableColumns`

For models that appear in list endpoints, declare the whitelist of sortable columns:

```php
protected array $sortableColumns = [
    'name',
    'created_at',
    'updated_at',
];
```

`BaseRepository::attachOrderByQuery()` validates `sort_by` against this list and falls back to the default if the requested column isn't in the whitelist.

### Casts via `casts()` method

```php
// ✅ Laravel 12 form
protected function casts(): array
{
    return [
        'gender' => PlayerGenderEnum::class,
        'dob' => 'date',
        'is_active' => 'boolean',
    ];
}

// ❌ Old form — don't use
protected $casts = [...];
```

### Relationships always have return type hints

```php
public function address(): BelongsTo
{
    return $this->belongsTo(Address::class, 'address_id', 'id');
}

public function mats(): HasMany
{
    return $this->hasMany(VenueMat::class, 'venue_id', 'id');
}
```

Always include explicit FK and PK column names — don't rely on Laravel's name guessing.

### Factory binding

System models override `newFactory()` to reach factories that live in `database/factories/`:

```php
protected static function newFactory(): VenueFactory
{
    return VenueFactory::new();
}
```

---

## Soft-delete + FK RESTRICT interplay

Already covered in [architecture.md](architecture.md#soft-delete--fk-restrict). Recap:

- `ON DELETE RESTRICT` (DB) blocks hard DELETE only
- `SoftDeletes` (Eloquent) issues UPDATE on `deleted_at`
- Therefore soft-delete bypasses the FK constraint — safe by design
- **Soft-delete children first, then parent** in repository methods
- **Never** call `forceDelete()` in module code without auditing FKs

---

## Junction (pivot) tables

Junction table names use both plural forms, joined: `clubs_players`, `tournament_matches`, `tournament_match_competitors`. Even though they hold many-to-many data, treat them as first-class tables with their own model and migration.

```php
Schema::create('clubs_players', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->ulid('club_id');
    $table->ulid('player_id');
    $table->date('joined_at');
    $table->date('left_at')->nullable();
    $this->editorLogs($table);

    $table->foreign('club_id')->references('id')->on('clubs')->onDelete('restrict')->onUpdate('cascade');
    $table->foreign('player_id')->references('id')->on('players')->onDelete('restrict')->onUpdate('cascade');
});
```

---

## Indexes

Add indexes proactively for columns that appear in `WHERE`, `ORDER BY`, or FK lookups. FK columns are auto-indexed by MySQL but not all DBs — declare them explicitly:

```php
$table->index('name');
$table->index(['tournament_id', 'category_id']);
$table->unique(['club_id', 'player_id', 'joined_at']);
```

---

## Pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| `$guarded = []` on a model | Use `$fillable` explicitly |
| Manually writing `created_by`, `updated_by` in migrations | Use `$this->editorLogs($table)` |
| `cascade` delete on the wrong relationship | Default to `restrict`; only use `cascade` for true sub-entities |
| String-backed enum stored as VARCHAR | Use int-backed enum + `unsignedTinyInteger` |
| `$casts` property instead of `casts()` method | Laravel 12 prefers the method form |
| Missing FK declaration in junction table | Always declare both FKs with `restrict`/`cascade` semantics |
| Storing date/time without a cast | Add `'field' => 'date'` or `'datetime'` to `casts()` |

---

## Related docs

- [wiki/Database.md](../../wiki/Database.md) — Authoritative schema reference (every table)
- [wiki/Enums.md](../../wiki/Enums.md) — All backend enums with values
- [wiki/Conventions.md](../../wiki/Conventions.md) — Project-wide conventions
- [architecture.md](architecture.md) — Repository pattern & multi-table writes
