---
name: haykal-domain-data
description: Use when defining a Spatie Data DTO — input to Action classes, output of API resources, or as a JSON-column value object on a model. Covers MapName(SnakeCaseMapper) for snake_case to camelCase mapping, readonly promoted properties, defaults, nested DTOs, hydrating with from(), and casting on Eloquent models.
---

# haykal-domain-data

`spatie/laravel-data` is the canonical DTO library across HiTaqnia. DTOs live under `domain/<Context>/Data/<Verb><Noun>Data.php` (input shapes) and `domain/<Context>/ValueObjects/<Name>.php` (immutable structured values stored as JSON).

## File header

```php
<?php

declare(strict_types=1);

namespace Domain\PropertyManagement\Data\Property;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
```

## Standard input DTO

```php
#[MapName(SnakeCaseMapper::class)]
final class CreatePropertyData extends Data
{
    public function __construct(
        public string $complexId,
        public array $name,                    // translatable: ['en' => '…', 'ar' => '…', 'ku' => '…']
        public ?array $description = null,
        public PropertyStatus $status = PropertyStatus::Available,
        public ?int $price = null,
    ) {}
}
```

`#[MapName(SnakeCaseMapper::class)]` lets the DTO accept snake_case input keys (FormRequest validated payload) while keeping camelCase property names in PHP. `from()` hydration handles the mapping:

```php
CreatePropertyData::from($request->validated());
// or
CreatePropertyData::from([
    'complex_id' => '01HV8…',
    'name' => ['en' => 'A1', 'ar' => '…', 'ku' => '…'],
    'price' => 120000,
]);
```

## Conventions

- **`final class`** for every DTO. Subclassing DTOs leads to weird mapper interactions — extract a base via composition instead.
- **Constructor-promoted public readonly properties.** Don't add setters. DTOs are immutable.
- **Defaults** for optional fields (`= null`, `= PropertyStatus::Available`, `= []`) — let the caller omit them.
- **Type every parameter.** `mixed` is a smell; if a field genuinely is polymorphic, model it as a union (`int|string`) or use a value object.
- **Enums as parameters** — declare the enum type directly. `from()` will cast strings/ints to the enum.
- **Nested DTOs / value objects** as parameters when a structured field has its own shape. Spatie Data handles the nested hydration.
- **No business logic.** A DTO is a typed record. Put behavior in Action classes (input DTO) or in the consuming model / accessor (value object).

## Value-object DTO (used as Eloquent cast)

```php
#[MapName(SnakeCaseMapper::class)]
final class PropertyMetadata extends Data
{
    public function __construct(
        public ?int $bedrooms = null,
        public ?int $bathrooms = null,
        public ?float $areaSqm = null,
        public array $amenities = [],
    ) {}

    public function hasAmenity(string $code): bool
    {
        return in_array($code, $this->amenities, strict: true);
    }
}
```

Cast on the model:

```php
protected $casts = [
    'metadata' => PropertyMetadata::class,
];
```

Stored as JSON in the column, hydrated to the value object on access. `Property::create(['metadata' => ['bedrooms' => 3, ...]])` works because `from()` runs.

## Optional / default values

Use `null` defaults rather than Spatie's `Optional` unless you specifically need to distinguish "key missing" from "key explicitly null". For the typical create/update split, use two DTOs (`CreatePropertyData`, `UpdatePropertyData`) where the update form makes everything nullable.

## Validation

DTOs **do not** carry validation rules in HiTaqnia. Validation belongs to FormRequest or to the calling Action's pre-checks. Keep DTOs as pure data carriers — this matches how hibayt-backend uses them.

(Spatie supports `#[Required]` etc. — we just don't use them. FormRequests are the single source of validation truth.)

## Output / response DTOs

For API responses, prefer `JsonResource` over Spatie Data — see **haykal-api-module**. Use Data for input shapes and for JSON-column value objects.

## Hydration sources

- `from(array)` — most common, used with `$request->validated()`
- `from(Model)` — when the DTO mirrors a model row
- `collect(iterable)` — for collections of DTOs

## Don't

- Don't omit `#[MapName(SnakeCaseMapper::class)]`. It's the convention; even when input happens to be camelCase, the attribute documents intent.
- Don't make properties non-readonly. Mutable DTOs are bug factories.
- Don't add validation attributes (`#[Required]`, `#[Min]`). Validate in FormRequest.
- Don't wrap a single value (`final class IdData { public string $id; }`). Use a primitive.
- Don't mix output and input shapes. `Create…Data` and `Update…Data` are separate; reading-back uses a JsonResource or a dedicated `…Snapshot` Data.

## References

- Source: `vendor/spatie/laravel-data/`
- Canonical examples in hibayt-backend: `domain/Core/Data/Complex/CreateComplexData.php`, `domain/Core/ValueObjects/Settings/ComplexSettings.php`
- See also: **haykal-domain-actions**, **haykal-models**, **haykal-api-module**
