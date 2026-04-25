---
name: haykal-filament-panel
description: Use when adding or modifying a Filament panel — admin, management, residents, operations, etc. Covers subclassing BasePanel, getId / customizePanel hooks, tenancy wiring (tenantModel, tenantSlugAttribute), Huwiya OAuth login, the access Gate, HaykalPlugin toggles, and the publish-theme command.
---

# haykal-filament-panel

Every Filament panel in a Haykal app subclasses `HiTaqnia\Haykal\Filament\BasePanel`. The base wires the middleware stack, Huwiya OAuth login, SPA mode, full-width layout, light theme, the Spatie translatable plugin, and convention-driven discovery for `app/Panels/<Name>/` (Resources, Pages, Widgets, Clusters).

## Class skeleton

`app/Providers/Panels/<Name>PanelProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers\Panels;

use App\Models\Complex;
use Filament\Panel;
use HiTaqnia\Haykal\Filament\BasePanel;
use HiTaqnia\Haykal\Filament\HaykalPlugin;

final class ManagementPanelProvider extends BasePanel
{
    protected function getId(): string
    {
        return 'management';
    }

    protected function customizePanel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Management')
            ->viteTheme('resources/css/filament/management/theme.css')
            ->plugin(
                HaykalPlugin::make()
                    ->withTranslatableTabs()
                    ->withAccessChecking(),
            );
    }

    protected function tenantModel(): ?string
    {
        return Complex::class;
    }

    protected function tenantSlugAttribute(): ?string
    {
        return 'slug';
    }
}
```

Register in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Panels\ManagementPanelProvider::class,
];
```

## Hooks

| Hook | Required | What it does |
|---|---|---|
| `getId(): string` | yes | Panel id used for URL prefix, session scope, resource discovery (`app/Panels/<Studly>/...`). Kebab-case is fine — it's `Str::studly`'d for the directory name. |
| `customizePanel(Panel $panel): Panel` | yes | Brand name, theme, extra plugins, navigation groups, dashboard widgets. Apply on top of the BasePanel defaults; return the same `$panel`. |
| `tenantModel(): ?string` | optional | Concrete tenant Eloquent model (e.g., `Complex::class`). Return `null` for super-admin / single-tenant panels. |
| `tenantSlugAttribute(): ?string` | optional | Column on the tenant model used as the URL slug (`slug`). `null` falls back to PK (ULID). |
| `loginPage(): string` | optional | Custom login page class. Default is `HuwiyaRedirectLogin`. |
| `defaultPlugins(): array` | optional | Override the SpatieTranslatablePlugin default. Rare. |

## What `BasePanel` already does

- Filament middleware stack (cookies, session, CSRF, locale)
- Authenticate with Huwiya OAuth → JWT (`HuwiyaRedirectLogin`)
- SPA mode (`->spa()`), full-width content, light-only theme, no global search
- Convention discovery:
  - Resources: `app/Panels/<Name>/Resources` → `App\Panels\<Name>\Resources\…`
  - Pages: `app/Panels/<Name>/Pages` → `App\Panels\<Name>\Pages\…`
  - Widgets: `app/Panels/<Name>/Widgets` → `App\Panels\<Name>\Widgets\…`
  - Clusters: `app/Panels/<Name>/Clusters` → `App\Panels\<Name>\Clusters\…`
- When `tenantModel()` is non-null: `->tenant($model, $slug)`, `->tenantMenu(false)`, and tenant middleware:
  - `FilamentTenancyMiddleware` — sets `Tenancy::setTenantId(...)`
  - `PermissionsTeamMiddleware` — forwards to Spatie `setPermissionsTeamId(...)`
  - `AccessCheckingMiddleware` — enforces `<panel-id>.access` Gate

Don't reimplement any of this in `customizePanel`.

## HaykalPlugin toggles

Per-panel feature flags via `HaykalPlugin::make()`:

- `withTranslatableTabs()` — installs Spatie translatable plugin (already in `BasePanel::defaultPlugins()`; calling this is a no-op unless you've overridden defaults).
- `withAccessChecking()` — appends `<panel-id>.access` Gate middleware to the auth stack. Required when the panel is gated (most are).

## Access Gate

Define one Gate per panel in `app/Providers/AppServiceProvider.php::boot()`:

```php
use Domain\Identity\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('management.access', fn (User $user) => $user->is_employee);
    Gate::define('admin.access',      fn (User $user) => $user->hasRole('admin'));
    Gate::define('residents.access',  fn (User $user) => $user->is_resident);
}
```

Pair with `HaykalPlugin::make()->withAccessChecking()` in the panel's `customizePanel()`.

## Theme

Scaffold the panel theme:

```bash
php artisan haykal:publish-theme management
```

This drops `resources/css/filament/management/theme.css` that `@import`s the Haykal base theme. Edit it for panel-specific tweaks. Reference it via `->viteTheme('resources/css/filament/management/theme.css')` in `customizePanel`.

After editing the theme, `bun run build` (or `bun run dev` in development) so Vite picks it up.

## Multi-panel apps

A Haykal app commonly has 5–10 panels (admin, management, operations, sales, hr, finance, security, settings, residents, documentation). Each is one provider class registered in `bootstrap/providers.php`. They share `BasePanel`'s defaults and only customize the brand, theme, plugin set, and tenancy.

## Custom auth page

Default is `HuwiyaRedirectLogin` (OAuth redirect). To replace it (e.g., a consent page):

```php
protected function loginPage(): string
{
    return CustomConsentPage::class;
}
```

`CustomConsentPage` must extend `Filament\Pages\SimplePage` and ultimately redirect to Huwiya — don't roll a local username/password login.

## Don't

- Don't use raw `Filament\PanelProvider` — always extend `BasePanel`.
- Don't replicate `BasePanel`'s middleware stack inside `customizePanel`. You'll get double execution and broken tenancy.
- Don't add `auth:sanctum` or session-only auth to panels. Auth is Huwiya.
- Don't share resource directories across panels. Each panel discovers its own `app/Panels/<Name>/Resources` tree.

## References

- Source: `haykal-monorepo/packages/haykal-filament/src/{BasePanel,HaykalPlugin}.php`
- Auth login: `haykal-monorepo/packages/haykal-filament/src/Auth/HuwiyaRedirectLogin.php`
- Middleware: `haykal-monorepo/packages/haykal-filament/src/Http/Middlewares/{FilamentTenancyMiddleware,SetPanelLocale,AccessCheckingMiddleware}.php`
- Starter README §"Create your Filament panel(s)"
- See also: **haykal-tenancy**, **haykal-filament-resource**, **haykal-filament-forms**
