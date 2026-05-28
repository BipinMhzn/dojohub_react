# EditorTrait

`Database\Traits\EditorTrait` is a PHP trait used inside migration classes. It provides a single method — `editorLogs(Blueprint $table)` — that appends a standard set of audit and soft-delete columns to any domain table's blueprint. Using the trait keeps all domain migrations consistent and removes the need to repeat the same six column definitions by hand.

## Source

```
database/traits/EditorTrait.php
```

## What `editorLogs()` adds

Calling `$this->editorLogs($table)` inside a `Schema::create` or `Schema::table` closure appends these columns in order:

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `created_by` | `unsignedBigInteger` | yes | FK to `users.id` (RESTRICT / CASCADE) |
| `updated_by` | `unsignedBigInteger` | yes | FK to `users.id` (RESTRICT / CASCADE) |
| `deleted_by` | `unsignedBigInteger` | yes | FK to `users.id` (RESTRICT / CASCADE) |
| `created_at` | `timestamp` | yes | Added by `$blueprint->timestamps()` |
| `updated_at` | `timestamp` | yes | Added by `$blueprint->timestamps()` |
| `deleted_at` | `timestamp` | yes | Added by `$blueprint->softDeletes()` |

The three FK columns reference `users.id` using Laravel's `foreign()` builder with no explicit `onDelete` / `onUpdate` override, meaning the database defaults apply (typically RESTRICT). This is intentional: soft-delete is an `UPDATE`, not a `DELETE`, so the RESTRICT on the user record is safe.

## How to use it in a migration

```php
use \Database\Traits\EditorTrait;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('my_table', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // ... domain columns ...
            $this->editorLogs($table);  // always call last
        });
    }
};
```

Always call `editorLogs()` after all domain columns so the audit columns appear at the end of the table definition.

## Which tables use it

Every domain table added after the initial Laravel scaffold uses `EditorTrait`. The framework tables (`users`, `cache`, `jobs`, `audits`) do **not** use the trait.

Tables that call `editorLogs()`:

- `tournament_rule_sets`
- `addresses`
- `venues`
- `venue_mats`
- `clubs`
- `players`
- `clubs_players`
- `referees`
- `coaches`
- `tournaments`
- `tournament_categories`
- `tournament_registrations`
- `tournament_registration_fees`
- `tournament_registration_payments`
- `tournament_weigh_ins`
- `tournament_registration_coaches`
- `tournament_brackets`
- `tournament_pools`
- `tournament_pool_competitors`
- `tournament_matches`
- `tournament_match_competitors`
- `tournament_match_referees`
- `tournament_match_events`
- `tournament_results`
- `clubs_coaches`

## Notes

- The `users` table is the only domain-style table that does **not** use `EditorTrait`. Its audit columns are defined manually with `onDelete('set null')` / `onUpdate('cascade')` overrides to handle self-referential FKs safely.
- `belt_ranks` and `countries` are lookup/reference tables with no audit columns at all.
- The `deleted_at` column enables Laravel's `SoftDeletes` trait on models. Because soft-delete fires an `UPDATE` rather than a `DELETE`, FK `RESTRICT` constraints on rows referencing soft-deleted records remain safe.
