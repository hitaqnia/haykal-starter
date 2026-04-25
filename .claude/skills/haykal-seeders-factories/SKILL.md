---
name: haykal-seeders-factories
description: Use when writing or editing a database seeder or model factory. Covers idempotent firstOrCreate/updateOrCreate patterns, three-language inline arrays for translatable columns, seeder chaining via $this->call, ULID-aware factories, and the Iraqi-phone seed convention from the starter's UserFactory.
---

# haykal-seeders-factories

Seeders are run repeatedly across dev / CI / staging — write them idempotent. Factories underpin tests and demo data — keep them realistic and deterministic where it matters.

## Seeder layout

- `database/seeders/DatabaseSeeder.php` — top-level orchestrator. Calls every other seeder via `$this->call([...])`.
- `database/seeders/<Domain>Seeder.php` — one per bounded context for system data (roles, settings, lookup tables).
- `database/seeders/Demo/Demo<Domain>Seeder.php` — demo / fixture data, never run in production.

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IdentitySeeder::class,
            CoreSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                Demo\DemoComplexSeeder::class,
                Demo\DemoUsersSeeder::class,
            ]);
        }
    }
}
```

## Idempotency

Always `firstOrCreate` or `updateOrCreate` — never `create()` from a seeder. Re-running `php artisan db:seed` must not blow up on duplicate keys.

```php
Role::firstOrCreate(
    ['name' => 'admin'],
    ['guard_name' => 'huwiya-web'],
);

Setting::updateOrCreate(
    ['key' => 'invoicing.due_days'],
    ['value' => 14],
);
```

## Translatable column data

When a model has translatable columns (`public array $translatable = ['name', 'description']`), pass arrays keyed by locale — Spatie's trait stores the JSON shape directly.

```php
Property::updateOrCreate(
    ['number' => 'A-101'],
    [
        'complex_id' => $complex->id,
        'name' => [
            'en' => 'Garden Apartment',
            'ar' => 'شقة بحديقة',
            'ku' => 'شوقەی باخچەدار',
        ],
        'description' => [
            'en' => 'Two-bedroom unit overlooking the central garden.',
            'ar' => '...',
            'ku' => '...',
        ],
    ],
);
```

All three locales (`en`, `ar`, `ku`) are required for HiTaqnia content. Don't ship seeders with only English.

## Calling external services

When a seed step needs to side-effect into an external service (payment provider, OTP), check the `Result`:

```php
$tenantResult = app(PaymentServiceContract::class)->createTenant(...);

if ($tenantResult->isFailure()) {
    throw new RuntimeException(
        "Payment service createTenant failed: " . $tenantResult->getError()->getMessage(),
    );
}
```

## Factories

- App-owned factories live under `database/factories/<Entity>Factory.php` OR `domain/<Context>/Database/Factories/<Entity>Factory.php`. The starter uses the domain location (`domain/Identity/Database/Factories/UserFactory.php`) — match its pattern for new domains.
- `composer.json` autoloads both `Database\\Factories\\` (`database/factories/`) and the domain ones via the domain PSR-4 root.
- Wire the factory on the model with `use HasFactory;` and (for domain factories) override `newFactory()` if Laravel's auto-discovery doesn't find it.

```php
<?php

declare(strict_types=1);

namespace Domain\PropertyManagement\Database\Factories;

use Domain\PropertyManagement\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'number' => 'A-' . $this->faker->unique()->numberBetween(100, 999),
            'name' => [
                'en' => $this->faker->streetName(),
                'ar' => $this->faker->streetName(),
                'ku' => $this->faker->streetName(),
            ],
            'status' => PropertyStatus::Available,
            'price' => $this->faker->numberBetween(50000, 500000),
        ];
    }

    public function occupied(): self
    {
        return $this->state(fn () => ['status' => PropertyStatus::Occupied]);
    }
}
```

Override `newFactory()` on the model if it's not in `Database\Factories\<Class>Factory`:

```php
protected static function newFactory(): PropertyFactory
{
    return PropertyFactory::new();
}
```

## ULIDs in factories

`HasUlids` generates the PK on `creating` — don't override `id` from the factory unless a test specifically needs a known id. Use `Property::factory()->create(['id' => '01HV8…'])` only for that case.

## Iraqi phone seed convention

The starter `UserFactory` (`domain/Identity/Database/Factories/UserFactory.php`) ships Iraqi-shaped phones (`+9647…`). When seeding users in any new app, mirror it — the `PhoneNumberCast` rejects malformed numbers.

```php
'phone' => '+9647' . $this->faker->numerify('#########'),
```

## States

Express variants as factory states (`->occupied()`, `->withMedia()`), not as separate factories. Tests stay readable: `Property::factory()->occupied()->create()`.

## Don't

- Don't seed timestamps explicitly. Let Eloquent set them.
- Don't `DB::table(...)->insert([...])` from seeders for app tables. You bypass casts, observers, and the tenant FK auto-fill.
- Don't seed test-only data into production. Gate demo seeders behind `app()->environment(['local','testing'])`.
- Don't import live data via a seeder. That's a Job or command, not a seeder.

## References

- Starter factory: `domain/Identity/Database/Factories/UserFactory.php`
- See also: **haykal-models**, **haykal-localization**, **haykal-tests**
