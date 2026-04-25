---
name: haykal-models
description: Use when creating or editing an Eloquent model (relationships, traits, casts, scopes, query builders, accessors, media collections, translatable attributes). Covers HasUlids, HasTenant, HasTranslations, casts (enums, PhoneNumberCast, value objects), $fillable ordering, custom QueryBuilders, booted() hooks, attribute accessors.
---

# haykal-models

Domain models live under `domain/<Context>/Models/<Entity>.php` (never under `app/Models/` — the starter only uses `Domain\Identity\Models\{User,Role,Permission}`). Every model uses ULIDs, soft deletes by default, and follows a strict structural ordering so they read the same across the codebase.

## File header

```php
<?php

declare(strict_types=1);

namespace Domain\PropertyManagement\Models;
```

## Trait stack

Common traits, in order:

- `HasUlids` (Laravel) — ULID primary keys (always)
- `HasTenant` (Haykal) — when the model is tenant-scoped (most are; see **haykal-tenancy**)
- `HasFactory` — when there's a factory
- `SoftDeletes` — default for domain models
- `HasTranslations` (Spatie) — when any column is translatable
- `InteractsWithMedia` (Spatie) — when the model owns uploads
- `HasRoles` (Spatie) — only on User-like models
- `Notifiable` — only on User-like models

## Structural order inside the class

```php
final class Property extends Model implements HasMedia
{
    use HasUlids, HasTenant, HasFactory, SoftDeletes, HasTranslations, InteractsWithMedia;

    // 1. Tenancy declaration (if HasTenant)
    protected string $tenantModel = Complex::class;

    // 2. $fillable — column order matches the migration; FK columns first, then user columns, then status/state
    protected $fillable = [
        'complex_id',
        'name',
        'description',
        'status',
        'price',
    ];

    // 3. $casts
    protected $casts = [
        'status' => PropertyStatus::class,           // enum
        'price' => 'decimal:2',
        'metadata' => PropertyMetadata::class,       // Spatie Data value object
    ];

    // 4. $translatable (Spatie translatable)
    public array $translatable = ['name', 'description'];

    // 5. $hidden / $appends if needed

    // 6. booted() hooks — auto-numbering, post-create defaults, etc.
    protected static function booted(): void
    {
        static::creating(function (Property $property) {
            if (empty($property->number)) {
                $property->number = PropertyNumberHelper::next($property->complex_id);
            }
        });
    }

    // 7. newEloquentBuilder() — return a custom QueryBuilder
    public function newEloquentBuilder($query): PropertyQueryBuilder
    {
        return new PropertyQueryBuilder($query);
    }

    // 8. registerMediaCollections() if InteractsWithMedia
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
        $this->addMediaCollection('documents');
    }

    // 9. Relationships — BelongsTo first, HasMany / HasManyThrough / BelongsToMany last
    public function complex(): BelongsTo { return $this->belongsTo(Complex::class); }
    public function units(): HasMany { return $this->hasMany(Unit::class); }

    // 10. Attribute accessors / mutators (PHP 8 attribute syntax)
    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->street}, {$this->city}",
        );
    }

    // 11. Local helper methods (only when business logic genuinely lives on the model)
}
```

## Casts

- **Enums**: `'status' => PropertyStatus::class` — the enum implements `HasLabel` + `HasColor` for Filament. See **haykal-domain-enums**.
- **Spatie Data DTOs as JSON columns**: `'metadata' => PropertyMetadata::class`. Stored as JSON in the DB, hydrated to the DTO on access.
- **Phone numbers**: `'phone' => HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast::class`. Normalizes to E.164 on set, returns a `PhoneNumber` value object on get.
- **Decimals**: `'price' => 'decimal:2'`. Don't store money as float.
- **Datetimes**: `'started_at' => 'datetime'`.
- **Booleans / arrays**: standard Laravel.

## Translatable columns

```php
use Spatie\Translatable\HasTranslations;

class Property extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];
}
```

Migration columns must be `jsonb` (PostgreSQL) — see **haykal-migrations**. Access:

```php
$property->name;                     // current locale
$property->getTranslation('name', 'ar');
$property->setTranslation('name', 'en', 'My Property');
```

## Custom Query Builders

Live under `domain/<Context>/QueryBuilders/<Entity>QueryBuilder.php`:

```php
namespace Domain\PropertyManagement\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;

class PropertyQueryBuilder extends Builder
{
    public function whereOccupied(): self
    {
        return $this->where('status', PropertyStatus::Occupied);
    }

    public function whereInComplex(string $complexId): self
    {
        return $this->where('complex_id', $complexId);
    }
}
```

Wire in the model with `newEloquentBuilder()`. Then `Property::whereOccupied()->get()` reads naturally. IDE hints: add a `@method` PHPDoc on the model class for static analysis.

## Booted hooks

Use for auto-population (numbering, defaults derived from FKs) — not for cross-cutting concerns better expressed as listeners. Keep them tight; complex post-create work belongs in an Action.

## What NOT to do

- **No `App\Models`** — domain models live under `domain/<Context>/Models/`. The only models in `domain/Identity/Models/` are `User`, `Role`, `Permission` and they extend framework / Spatie base classes (see starter README).
- **No business logic in scopes that mutate state.** Scopes are read-only.
- **No `protected $guarded = []` shortcut.** Always declare `$fillable` explicitly.
- **No `protected $dates = [...]`** — that's pre-Laravel 8. Use `$casts` with `'datetime'`.
- **No `Carbon::now()` in `creating` hooks.** Use `now()` and let Laravel's Carbon mock work in tests.
- **Don't reach for `App\Traits`** for tenancy / soft deletes / translatable. Use the Haykal/Spatie traits directly.

## References

- Source: `haykal-monorepo/packages/haykal-core/src/Identity/{ValueObjects/PhoneNumber,Casts/PhoneNumberCast}.php`
- Tenancy trait: `haykal-monorepo/packages/haykal-core/src/Tenancy/Concerns/HasTenant.php`
- Starter example: `domain/Identity/Models/User.php`, `domain/Identity/QueryBuilders/UserQueryBuilder.php`, `domain/Identity/Database/Factories/UserFactory.php`
- See also: **haykal-tenancy**, **haykal-migrations**, **haykal-domain-enums**, **haykal-domain-data**
