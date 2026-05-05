---
name: haykal-filament-panel
description: Use when adding or modifying a Filament panel — admin, management, residents, operations, etc. Covers subclassing BasePanel, getId / customizePanel hooks, tenancy wiring (tenantModel, tenantSlugAttribute), the Huwiya OAuth consent login, locale-aware fonts, and the publish-theme command.
---

# haykal-filament-panel

Every Filament panel in a Haykal app subclasses `HiTaqnia\Haykal\Filament\BasePanel`. The base wires the middleware stack, the Huwiya OAuth consent-login page, SPA mode, full-width layout, light theme, no top bar, locale-aware fonts (Outfit/Tajawal/Noto Sans Arabic), the Spatie translatable plugin, and convention-driven discovery for `app/Panels/<Name>/` (Resources, Pages, Widgets, Clusters).

## Class skeleton

`app/Providers/Panels/<Name>PanelProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers\Panels;

use App\Models\Complex;
use Filament\Panel;
use HiTaqnia\Haykal\Filament\BasePanel;

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
            ->viteTheme('resources/css/filament/management/theme.css');
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
| `loginPage(): string` | optional | Custom login page class. Default is `HuwiyaConsentLogin`. |
| `defaultPlugins(): array` | optional | Override the SpatieTranslatablePlugin default. Rare. |
| `fontFamiliesByLocale(): array` | optional | Override the locale → font family map. Defaults: `default→Outfit`, `en→Outfit`, `ar→Tajawal`, `ku→Noto Sans Arabic`. |

## What `BasePanel` already does

- Filament middleware stack (cookies, session, CSRF).
- Authenticate with Huwiya OAuth → JWT (`HuwiyaConsentLogin` — single-button consent page, translated through `haykal-filament::auth.login.*`).
- SPA mode (`->spa()`), full-width content, light-only theme, no global search, no topbar.
- Locale-aware Bunny font (`Outfit` / `Tajawal` / `Noto Sans Arabic`) via `Panel::font(...)` — the closure resolves the active locale on every render.
- Convention discovery:
  - Resources: `app/Panels/<Name>/Resources` → `App\Panels\<Name>\Resources\…`
  - Pages: `app/Panels/<Name>/Pages` → `App\Panels\<Name>\Pages\…`
  - Widgets: `app/Panels/<Name>/Widgets` → `App\Panels\<Name>\Widgets\…`
  - Clusters: `app/Panels/<Name>/Clusters` → `App\Panels\<Name>\Clusters\…`
- When `tenantModel()` is non-null: `->tenant($model, $slug)`, `->tenantMenu(false)`, and tenant middleware:
  - `FilamentTenancyMiddleware` — sets `Tenancy::setTenantId(...)`
  - `PermissionsTeamMiddleware` — forwards to Spatie `setPermissionsTeamId(...)`

Don't reimplement any of this in `customizePanel`.

## Global Filament defaults (per-app provider)

For app-wide Filament UX defaults (no "Create another", slide-over column manager + filters, em-dash placeholder + "Click to copy" tooltip on copyable text), subclass `BaseFilamentServiceProvider` once in `app/Providers/FilamentServiceProvider.php` and register it in `bootstrap/providers.php`. Override individual `configure*()` hooks to relax or extend a single concern. See haykal-filament's README for the full list.

## Theme

Scaffold the panel theme:

```bash
php artisan haykal:publish-theme management
```

This drops `resources/css/filament/management/theme.css` that `@import`s Filament's default theme and the Haykal base theme directly from the vendor path. Edit it for panel-specific tweaks — or override individual tokens (`--filament-primary-rgb`, `--filament-shell`, `--filament-radius`, …) in a `@theme {}` block to re-skin without rewriting rules. Reference it via `->viteTheme('resources/css/filament/management/theme.css')` in `customizePanel`.

After editing the theme, `bun run build` (or `bun run dev` in development) so Vite picks it up.

## Multi-panel apps

A Haykal app commonly has 5–10 panels (admin, management, operations, sales, hr, finance, security, settings, residents, documentation). Each is one provider class registered in `bootstrap/providers.php`. They share `BasePanel`'s defaults and only customize the brand, theme, and tenancy.

## Custom auth page

Default is `HuwiyaConsentLogin` (single-button consent → OAuth redirect). To replace it (e.g., an immediate redirect, custom layout):

```php
protected function loginPage(): string
{
    return CustomLoginPage::class;
}
```

`CustomLoginPage` must extend `Filament\Pages\SimplePage` and ultimately redirect to Huwiya — don't roll a local username/password login.

## Don't

- Don't use raw `Filament\PanelProvider` — always extend `BasePanel`.
- Don't replicate `BasePanel`'s middleware stack inside `customizePanel`. You'll get double execution and broken tenancy.
- Don't add `auth:sanctum` or session-only auth to panels. Auth is Huwiya.
- Don't share resource directories across panels. Each panel discovers its own `app/Panels/<Name>/Resources` tree.

## References

- Source: `haykal-monorepo/packages/haykal-filament/src/BasePanel.php`
- Auth login: `haykal-monorepo/packages/haykal-filament/src/Auth/HuwiyaConsentLogin.php`
- Middleware: `haykal-monorepo/packages/haykal-filament/src/Http/Middlewares/FilamentTenancyMiddleware.php`
- Global defaults: `haykal-monorepo/packages/haykal-filament/src/BaseFilamentServiceProvider.php`
- Starter README §"Create your Filament panel(s)"
- See also: **haykal-tenancy**, **haykal-filament-resource**, **haykal-filament-forms**
