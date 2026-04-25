---
name: haykal-overview
description: Read first when starting any non-trivial task in a HiTaqnia / Haykal Laravel app. Routes to the right specialist haykal-* skill for the task type (model, migration, action, Filament resource, API endpoint, tests, localization, etc.).
---

# haykal-overview

A HiTaqnia Laravel application built on the Haykal framework follows a strict DDD layout, ULID-everywhere PKs, multi-tenant scoping, three-language localization (en, ar, ku), and a `Result<T>` pattern for recoverable failures. This skill is the index — read it first, then load the specialist skill for the work at hand.

## Directory layout

```
app/
├── Apis/<Module>/                 API controllers, requests, resources (per module)
│   ├── Controllers/<Group>/<Entity>Controller.php
│   ├── Requests/<Group>/<Verb><Entity>Request.php
│   └── Resources/<Group>/<Entity>Resource.php
├── Panels/<Name>/                 Filament panel discovery roots
│   ├── Resources/<Entity>Resource.php
│   ├── Resources/<Entity>/Schemas/{<Entity>Form,<Entity>Table,<Entity>Infolist}.php
│   ├── Resources/<Entity>/Pages/{List,Create,Edit,View}<Entity>.php
│   ├── Pages/                     custom panel pages
│   ├── Widgets/
│   └── Clusters/
└── Providers/
    ├── Apis/<Module>ApiProvider.php   one per API module (subclass ApiProvider)
    └── Panels/<Name>PanelProvider.php one per Filament panel (subclass BasePanel)

domain/<Context>/                  bounded context — one folder per domain
├── Actions/<Noun>/<Verb><Noun>Action.php   business operations, return Result<T>
├── Data/<Verb><Noun>Data.php               Spatie Data DTOs (input/output)
├── Enums/<Name>.php                        backed enums w/ HasLabel + HasColor
├── Models/<Entity>.php                     Eloquent models
├── QueryBuilders/<Entity>QueryBuilder.php  custom query builders
├── Rules/<Name>Rule.php                    custom ValidationRule
├── ValueObjects/<Name>.php                 (optional) immutable value objects
├── Casts/<Name>Cast.php                    (optional) Eloquent attribute casts
├── Events/, Listeners/, Notifications/     (optional)
└── <Context>Errors.php                     static factory of domain Errors

database/
├── migrations/                    Laravel timestamp-named migrations (jsonb for translatable)
├── factories/                     when factories are app-owned (also see domain/<Ctx>/Database/Factories)
└── seeders/

lang/<en|ar|ku>/                   three locales — every user-facing string ships in all three
├── auth.php, common.php, validation.php
├── domains/<context>/enums.php
├── panels/<panel-id>/resources/<resource-kebab-plural>.php
├── apis/<module>/requests/<request>.php
└── errors/<context>.php

routes/
├── api.php                        mounts route files under routes/api/
├── api/<module>-api.php           per-module API routes
└── web.php

support/                           app-wide utilities (helpers, base classes, services)
packages/                          path-repository packages (see composer.json)
tests/                             PHPUnit (Feature/, Unit/)
```

Autoload roots (`composer.json`):
- `App\\` → `app/`
- `Domain\\` → `domain/`
- `Support\\` → `support/`
- `Database\\Factories\\` → `database/factories/`
- `Database\\Seeders\\` → `database/seeders/`

## Which skill to load

| Task | Skill |
|---|---|
| Add or edit an Eloquent model (relationships, casts, query builders, traits) | **haykal-models** |
| Write a migration | **haykal-migrations** |
| Write a seeder or factory | **haykal-seeders-factories** |
| Add or edit translation files | **haykal-localization** |
| Write or call a domain Action class | **haykal-domain-actions** |
| Write a Spatie Data DTO | **haykal-domain-data** |
| Add a backed enum used in models / Filament UI | **haykal-domain-enums** |
| Decide between `Result<T>::failure(...)` and throwing; assign domain error codes | **haykal-result-pattern** |
| Wire multi-tenant scoping on a model or panel | **haykal-tenancy** |
| Create or modify a Filament panel | **haykal-filament-panel** |
| Create or modify a Filament resource (Form/Table/Infolist split, pages) | **haykal-filament-resource** |
| Build a translatable form, Mapbox picker, or other Haykal Filament component | **haykal-filament-forms** |
| Add an API module / controller / request / resource / route | **haykal-api-module** |
| Write or run tests | **haykal-tests** |
| Group methods inside a multi-section class with section headers | **haykal-class-sections** |

## Cross-cutting rules

- **PHP 8.3+, `declare(strict_types=1)`** at the top of every new file under `domain/`, `support/`, `app/Apis/`, `app/Panels/`. Match the style in `haykal-monorepo/packages/haykal-core/src/*`.
- **ULIDs everywhere** for primary keys and foreign keys. `HasUlids` trait on every model; `$table->ulid('id')->primary()` and `$table->foreignUlid(...)` in migrations.
- **Soft deletes** on most domain models (`SoftDeletes` trait + `$table->softDeletes()` in migrations).
- **All user-facing strings come from `__()`** keyed under `lang/<locale>/...`. Three locales: `en`, `ar`, `ku`. See **haykal-localization**.
- **Recoverable business failures return `Result::failure(<DomainErrors>::name())`** — no exceptions. Infrastructure failures (DB down, programming errors) throw. See **haykal-result-pattern**.
- **API endpoints always return through `ApiResponse::ok|created|paginated|noContent|validationError|businessError|...`**. The Haykal envelope shape is non-negotiable. See **haykal-api-module**.
- **No `App\Models\` directory** — domain models live under `domain/<Context>/Models/` and are referenced by FQCN. The starter only ships `Domain\Identity\Models\{User,Role,Permission}`.
- **Every Filament panel extends `HiTaqnia\Haykal\Filament\BasePanel`**, every Filament resource extends `HiTaqnia\Haykal\Filament\Resources\BaseResource`, every API module extends `HiTaqnia\Haykal\Api\ApiProvider`. Don't reach for vanilla Laravel/Filament base classes.
- **Authentication is Huwiya OAuth2 → JWT** by default. The `web` guard is `huwiya-web`, the API guard is `huwiya-api`. Don't add a separate `auth:sanctum` middleware unless you've talked to the team.
- **Generic Laravel/Filament/Pest knowledge comes from Laravel Boost** (see `CLAUDE.md` at the project root). The `haykal-*` skills only encode HiTaqnia-specific deltas on top.

## Reference

- Starter README: `README.md`
- Haykal package source: `haykal-monorepo/packages/{haykal-core,haykal-api,haykal-filament}/src/`
- Canonical example app: `hibayt-backend/` (older Laravel 12 stack — patterns translate cleanly to Laravel 13)
- Boost guidelines: `CLAUDE.md` (auto-generated by `php artisan boost:install`)
