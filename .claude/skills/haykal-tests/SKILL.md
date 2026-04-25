---
name: haykal-tests
description: Use when writing or running tests in a HiTaqnia / Haykal app. Covers PHPUnit (not Pest), Feature vs Unit split, the SQLite :memory: convention from phpunit.xml, RefreshDatabase / DatabaseTransactions, faking Huwiya auth, the make test / composer test runners.
---

# haykal-tests

HiTaqnia uses **PHPUnit** (not Pest). Tests run against an in-memory SQLite database configured in `phpunit.xml`. Feature tests cover HTTP / Filament / job behavior; Unit tests cover Action classes, value objects, and domain helpers.

## Layout

```
tests/
├── TestCase.php              base class extending Laravel's TestCase
├── Feature/                  HTTP, Filament, jobs, console commands
│   └── <Domain>/<…>Test.php
└── Unit/                     pure domain tests — Actions, value objects, enums, helpers
    └── <Domain>/<…>Test.php
```

The `Tests\` autoload root maps to `tests/` (`composer.json` `autoload-dev`).

## Test class shape

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\PropertyManagement;

use Domain\PropertyManagement\Models\Property;
use Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PropertyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_properties(): void
    {
        $user = User::factory()->create();
        Property::factory()->count(3)->create();

        $response = $this->actingAs($user, 'huwiya-api')
            ->getJson('/api/management/properties');

        $response
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(3, 'data.items');
    }

    public function test_creating_a_property_with_negative_price_returns_business_error(): void
    {
        $user = User::factory()->create();
        $complex = Complex::factory()->create();

        $response = $this->actingAs($user, 'huwiya-api')
            ->withHeader('X-Complex-Id', $complex->id)
            ->postJson('/api/management/properties', [
                'name' => ['en' => 'A1', 'ar' => 'أ1', 'ku' => 'ئ1'],
                'price' => -5,
                'status' => 'available',
            ]);

        $response
            ->assertStatus(409)
            ->assertJsonPath('success', 0)
            ->assertJsonPath('code', 1200);   // PropertyManagementErrors::pricesCannotBeNegative()
    }
}
```

Conventions:
- **`final class`** — tests aren't extended.
- **`extends Tests\TestCase`** — never `\PHPUnit\Framework\TestCase` directly.
- **`test_*` snake_case method names** with `: void` return type. The descriptive name is the assertion — keep it readable.
- **`use RefreshDatabase`** for any Feature test that touches the DB. Use `DatabaseTransactions` only when you need cross-test data (rare).
- **Arrange / Act / Assert** sections, blank line between each.

## Database

`phpunit.xml` sets `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`. Every Feature test gets a fresh schema via `RefreshDatabase`.

Notes on SQLite limitations:
- Some PostgreSQL features won't run under SQLite (PostGIS, JSONB operators). For those tests, gate on `DB::getDriverName()` or run them only against the Docker Postgres in CI.
- `jsonb` columns become plain `json` under SQLite. Translatable columns still work — Spatie reads/writes JSON either way.

## Auth in tests

Huwiya is the auth backend. Stub it with `actingAs`:

```php
$user = User::factory()->create();

// API requests
$this->actingAs($user, 'huwiya-api')->getJson('/api/...');

// Filament panel pages
$this->actingAs($user, 'huwiya-web')->get('/management');
```

The factory in `domain/Identity/Database/Factories/UserFactory.php` produces Iraqi-shaped phones — see **haykal-seeders-factories**.

For tests that explicitly exercise Huwiya OAuth flows, mock the SDK (see `huwiya-laravel/docs/testing.md` if present).

## Tenancy in tests

When testing tenant-scoped endpoints, set the active tenant:

```php
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;

$complex = Complex::factory()->create();
Tenancy::setTenantId($complex->id);
```

Or — for HTTP tests — pass the resolving header:

```php
$this->withHeader('X-Complex-Id', $complex->id)->getJson('/api/...');
```

Reset between tests via `RefreshDatabase`'s `setUp` chain (Tenancy is bound per-request).

## Filament tests

Use Filament's testing helpers:

```php
use Filament\Pages\Dashboard;
use function Filament\Testing\livewire;

public function test_dashboard_loads_for_employee(): void
{
    $user = User::factory()->employee()->create();

    $this->actingAs($user, 'huwiya-web')
        ->get(\App\Providers\Panels\ManagementPanelProvider::class)
        ->assertOk();
}
```

For Livewire components, see Filament's `livewire(...)->assertSee(...)` API.

## Unit tests

For domain Actions and value objects, prefer Unit tests — they're faster and more focused:

```php
namespace Tests\Unit\Tasks;

use Domain\Tasks\Actions\Task\ChangeTaskStatusAction;
use Domain\Tasks\Enums\TaskStatus;
use Domain\Tasks\Models\Task;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ChangeTaskStatusActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_task_cannot_be_completed_again(): void
    {
        $task = Task::factory()->completed()->create();

        $result = ChangeTaskStatusAction::execute($task, TaskStatus::Completed);

        $this->assertTrue($result->isFailure());
        $this->assertSame(1402, $result->getError()->getCode());
    }
}
```

## Running

```bash
composer test                  # config:clear + php artisan test
make test                      # same, inside the Docker container
php artisan test --filter=PropertyApiTest
php artisan test --filter=test_authenticated_user_can_list_properties
```

CI runs `composer test` after `composer install` and `php artisan migrate --env=testing` (if your test DB isn't in-memory).

## Don't

- Don't write Pest tests in this repo. PHPUnit only — Pest infra isn't installed.
- Don't `markTestSkipped` to silence broken tests. Either fix or delete.
- Don't share state via static properties between tests. Use `setUp` to recreate fixtures.
- Don't hit real Huwiya / payment / OTP services in tests. Fake them via container bindings or env flags (`OTP_FAKE=true`).

## References

- Test runner config: `phpunit.xml`
- Composer scripts: `composer.json` (`scripts.test`)
- Starter Makefile: `Makefile` (`test:` target)
- See also: **haykal-seeders-factories**, **haykal-tenancy**, **haykal-result-pattern**
