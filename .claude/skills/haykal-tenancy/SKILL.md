---
name: haykal-tenancy
description: Use when a model needs to be scoped to a tenant (Complex, Agency, etc.), when wiring a Filament panel that has a tenant, when writing a migration that needs the tenant FK column, or when adjusting middleware that depends on the active tenant. Covers HasTenant trait, $tenantModel/$tenantForeignKey, TenantScope global scope, and the tenancy resolution flow.
---

# haykal-tenancy

Most HiTaqnia apps are multi-tenant. Tenancy is opt-in per model via the `HasTenant` trait — no inheritance, no base model. The trait wires a global scope (`TenantScope`) and an auto-fill on `creating`. Single source of truth for the active tenant id is `Tenancy::getTenantId()`, set per-request by middleware.

## Classes to use

```php
use HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant;  // packages/haykal-core/src/Tenancy/Concerns/HasTenant.php
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;             // packages/haykal-core/src/Tenancy/Tenancy.php
use HiTaqnia\Haykal\Core\Tenancy\TenantScope;         // packages/haykal-core/src/Tenancy/TenantScope.php
```

## Defining the tenant model

The starter does **not** ship a tenant — every app picks its own. A typical setup:

```php
namespace App\Models;

use HiTaqnia\Haykal\Core\Tenancy\Models\Tenant;

final class Complex extends Tenant
{
    // app-specific columns, relations, accessors
}
```

Migration uses ULID PK + the columns the app needs.

For multi-tenant-type apps (e.g., Agency + DevelopmentCompany), define one subclass of `Tenant` per type.

## Marking a model as tenant-owned

```php
namespace Domain\PropertyManagement\Models;

use App\Models\Complex;
use HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Property extends Model
{
    use HasUlids, HasTenant, SoftDeletes;

    protected string $tenantModel = Complex::class;

    // Optional: override only when this model's FK column doesn't follow
    // the global default (`tenant_id`). E.g., when there are multiple
    // tenant types and `properties` belongs to `agencies`:
    // protected string $tenantForeignKey = 'agency_id';
}
```

The trait `HasTenant`:
1. Adds `TenantScope` as a global scope — every query auto-filters to the active tenant. Rows with a `NULL` tenant FK remain visible (treated as shared).
2. On `creating`, fills the tenant FK from `Tenancy::getTenantId()` if the column is empty and a tenant is active.
3. Exposes a `tenant()` BelongsTo relation pointing at `$tenantModel`.

## Migration column

Match the FK name declared on the model. Default convention is `tenant_id`; multi-type apps use `<tenant_type>_id`.

```php
$table->foreignUlid('complex_id')->constrained('complexes')->cascadeOnDelete();
```

Don't add a manual index on the FK — `foreignUlid` already does.

## Bypassing the scope

When you legitimately need to read across tenants (admin reports, system jobs):

```php
Property::withoutGlobalScope(TenantScope::class)->get();
// or
Property::query()->withoutGlobalScopes()->get();
```

Always justify with a comment when you bypass — it's the kind of code that bites later.

## Filament panels with tenancy

In a `BasePanel` subclass:

```php
protected function tenantModel(): ?string
{
    return Complex::class;
}

protected function tenantSlugAttribute(): ?string
{
    return 'slug';
}
```

`BasePanel` auto-installs the tenant middleware stack — `FilamentTenancyMiddleware` (sets `Tenancy::setTenantId(...)` from the resolved Filament tenant) and `PermissionsTeamMiddleware` (forwards into Spatie's `setPermissionsTeamId`). See **haykal-filament-panel**.

For super-admin / single-tenant panels, return `null` from `tenantModel()`.

## API tenancy

API requests pass tenant context via headers (e.g., `X-Complex-Id`). Each `ApiProvider` declares the security scheme:

```php
protected function additionalSecuritySchemes(): array
{
    return [
        'complex' => SecurityScheme::apiKey('header', 'X-Complex-Id'),
    ];
}
```

A middleware (typically `app/Apis/<Module>/Middlewares/Resolve<Module>ApiContext.php`) reads the header, validates the user has access to that tenant, and calls `Tenancy::setTenantId(...)`. After that, all `HasTenant` queries are scoped automatically.

## Spatie permissions per-tenant

When permissions are tenant-scoped (default off in the starter), enable in `config/permission.php` (`teams = true`) and slot the `haykal.permissions.team` middleware into the relevant route groups / Filament panels **after** the tenancy resolver. `BasePanel` does this automatically when `tenantModel()` is set.

## References

- Source: `haykal-monorepo/packages/haykal-core/src/Tenancy/{Tenancy,TenantScope}.php`, `Tenancy/Concerns/HasTenant.php`
- Filament wiring: `haykal-monorepo/packages/haykal-filament/src/{BasePanel,Http/Middlewares/FilamentTenancyMiddleware}.php`
- Starter README §"Define your tenant model(s)" and §"Mark tenant-owned models with HasTenant"
- See also: **haykal-models**, **haykal-migrations**, **haykal-filament-panel**, **haykal-api-module**
