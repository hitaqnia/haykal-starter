---
name: haykal-migrations
description: Use when writing or editing a Laravel database migration. Covers naming, ULID PKs, foreignUlid FKs, jsonb columns for Spatie translatable fields, soft deletes, ordering of column declarations, and PostGIS/Magellan geo columns.
---

# haykal-migrations

Migrations live under `database/migrations/` and are owned by the application. The schema is PostgreSQL (PostGIS-enabled) — assumptions follow.

## File naming

`YYYY_MM_DD_HHmmss_<verb>_<table>.php`. Verbs:

- `create_<table>_table` — new table
- `add_<column[_and_column…]>_to_<table>_table` — add columns
- `remove_<column>_from_<table>_table` — drop columns
- `rename_<old>_to_<new>_in_<table>_table` — column rename
- `change_<column>_in_<table>_table` — column type change
- `create_<a>_<b>_pivot_table` — pivot tables (alphabetical singular nouns)

Generate with `php artisan make:migration` so the timestamp prefix is correct.

## Standard new-table skeleton

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('complex_id')->constrained()->cascadeOnDelete();

            $table->string('number');
            $table->jsonb('name');                  // translatable (en/ar/ku)
            $table->jsonb('description')->nullable();

            $table->string('status');
            $table->decimal('price', 12, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
```

## Column ordering convention

1. PK (`ulid('id')->primary()`)
2. Tenant / parent FKs (`foreignUlid(...)->constrained()`)
3. Other FKs
4. Identity columns (number, slug, code)
5. Translatable JSONB columns (`name`, `description`)
6. Plain string / numeric / boolean columns
7. Status / state / enum columns
8. Geo columns (Magellan)
9. `timestamps()`
10. `softDeletes()` when applicable

## ULIDs

- **Always** ULIDs — never auto-increment integers, never UUID v4 for new tables.
- Primary key: `$table->ulid('id')->primary();`
- Foreign keys: `$table->foreignUlid('complex_id')->constrained();` — `constrained()` infers the table name from the FK column. Pass an explicit name when it doesn't match: `->constrained('complexes')`.
- Cascading: prefer `cascadeOnDelete()` for child tables when the child cannot exist without the parent. Use `nullOnDelete()` for optional references. Document anything else.
- Don't add a separate `$table->index('complex_id')` — `foreignUlid` already does.

## Translatable columns

Spatie Translatable stores translations as JSON. PostgreSQL — use `jsonb` (binary, queryable, indexed):

```php
$table->jsonb('name');
$table->jsonb('description')->nullable();
```

Match the model's `public array $translatable = ['name', 'description'];` See **haykal-models**.

## Soft deletes

```php
$table->softDeletes();
```

Default for any domain entity. Skip for pure pivot tables and append-only logs.

## Tenancy FK

When the model uses `HasTenant` — see **haykal-tenancy** — add the FK column matching the model's `$tenantForeignKey` (default `tenant_id`). Most apps name it for the tenant type:

```php
$table->foreignUlid('complex_id')->constrained()->cascadeOnDelete();
```

## PostGIS / Magellan

For point/polygon columns:

```php
use Clickbar\Magellan\Schema\MagellanBlueprint;

Schema::create('locations', function (MagellanBlueprint $table) {
    $table->ulid('id')->primary();
    $table->point('coordinates');           // SRID 4326
    $table->polygon('boundary')->nullable();
    $table->timestamps();
});
```

Use `MagellanBlueprint` (typehint) so static analysis knows about `point()` / `polygon()` / etc.

## Pivot tables

```php
Schema::create('property_user', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
    $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
    $table->unique(['property_id', 'user_id']);
    $table->timestamps();
});
```

Alphabetical singular table names (`property_user`, not `user_property`).

## Add-column migrations

```php
public function up(): void
{
    Schema::table('properties', function (Blueprint $table) {
        $table->jsonb('amenities')->nullable()->after('description');
    });
}

public function down(): void
{
    Schema::table('properties', function (Blueprint $table) {
        $table->dropColumn('amenities');
    });
}
```

Always provide a meaningful `down()` — even if it's `Schema::dropIfExists`. Don't ship one-way migrations.

## Don't

- Don't `DB::statement('ALTER TABLE …')` when Schema can express it.
- Don't add `nullable()->default(...)` without thinking — `null` and "no value" are different. Use one or the other.
- Don't write data backfills in the same migration as schema changes for tables with non-trivial row counts. Split into two migrations or use a deferred job.
- Don't rename FK columns without also updating the model's `$tenantForeignKey` / relation methods / `$fillable`.

## References

- Starter migrations: `database/migrations/2026_04_23_235602_create_users_table.php` and siblings — minimal but follow these rules.
- See also: **haykal-models**, **haykal-tenancy**, **haykal-localization**
