# HiTaqnia Haykal Starter

A Laravel 13 application with the [Haykal](../haykal) suite pre-wired — Huwiya-backed authentication, a Filament-ready base, the Haykal API envelope, Scramble, and the shared Haykal middleware stack. Clone this directory as the starting point for any new HiTaqnia project.

---

## Table of contents

- [Directory structure](#directory-structure)
- [What is already wired up](#what-is-already-wired-up)
- [Required configuration](#required-configuration)
- [Local development with Docker](#local-development-with-docker)
- [Next steps for the developer](#next-steps-for-the-developer)
- [Running the application](#running-the-application)
- [Reference](#reference)

---

## Directory structure

The starter follows the HiTaqnia DDD layout: Laravel's `app/` for framework glue and HTTP / Filament wiring, `domain/` for business logic grouped by bounded context, `support/` for app-wide utilities, and `packages/` for in-tree Composer path repositories.

```
haykal-starter/
├── app/                   Standard Laravel directory: providers, console, HTTP.
├── domain/                DDD domains — each subdirectory is one bounded context.
│   └── Identity/          Standalone identity layer — owned by the app.
│       ├── Models/
│       │   ├── User.php              Authenticatable + Huwiya claim sync + phone cast + query builder
│       │   ├── Role.php              extends Spatie\Permission\Models\Role (ULID-keyed)
│       │   └── Permission.php        extends Spatie\Permission\Models\Permission (ULID-keyed)
│       ├── QueryBuilders/
│       │   └── UserQueryBuilder.php  wherePhoneNumber() / getByPhoneNumber()
│       └── Database/Factories/
│           └── UserFactory.php       Iraqi-phone seed data
├── support/               App-wide utilities. Empty — add as needs emerge.
├── packages/              In-tree path repositories. Empty — `packages/*` is already declared as a path repo in composer.json.
├── deployment/            Dev-only Docker assets (Dev.Dockerfile, php.dev.ini).
├── docker-compose.yaml    Full dev stack (app + horizon + scheduler + postgres + redis + minio + mailpit).
├── Makefile               make setup / docker-* / migrate* / artisan / rename-project etc.
└── ...                    Standard Laravel: bootstrap/, config/, database/, public/, resources/, routes/, storage/, tests/.
```

Autoload roots declared in `composer.json`:

- `App\` → `app/`
- `Domain\` → `domain/`
- `Support\` → `support/`

---

## What is already wired up

Everything below is installed, configured, and migrated. No action needed unless something needs to be customized.

### Packages

| Package | Purpose |
|---|---|
| `hitaqnia/haykal` | Metapackage — installs `haykal-core`, `haykal-api`, and `haykal-filament` together. |
| `hitaqnia/huwiya-laravel` | Authentication via the Huwiya Identity Provider. |
| Filament 5.5 | Admin panel framework. |
| Spatie permission / media-library / translatable / data | Roles, attachments, model translations, DTOs. Installed directly by the starter. |
| Dedoc Scramble | Auto-generated OpenAPI documentation. |
| Laravel Horizon | Queue monitoring. Installed directly by the starter. |
| Flysystem S3 + Predis | Storage and Redis clients. Installed directly by the starter. |

### Authentication

- `config/auth.php` has the `huwiya-web` guard as the default `web` guard and a `huwiya-api` guard for stateless API requests.
- `config/auth.php` points the `users` provider at `Domain\Identity\Models\User` — the application's own standalone `Authenticatable` subclass. It composes `InteractsWithHuwiya` (from the Huwiya SDK), Spatie `HasRoles`, Media Library's `InteractsWithMedia`, ULIDs, and soft deletes, and carries the team's default claim-sync map inline (`name`, `phone`, `email`, `locale`, `zoneinfo`, `theme`). Edit `domain/Identity/Models/User.php` directly to add relations, scopes, or override any Huwiya SDK hook (`shouldAutoRegister`, `resolveHuwiyaConflict`, etc.) — the file is yours.

### Identity models

The `Domain\Identity\Models` namespace ships three application-owned classes. They lean on Spatie directly and on Haykal utilities (phone cast, query builder) — no haykal-provided base class anywhere in the inheritance chain:

| Model | Extends | Wired in |
|---|---|---|
| `Domain\Identity\Models\User` | `Illuminate\Foundation\Auth\User` (Authenticatable) | `config/auth.php` |
| `Domain\Identity\Models\Role` | `Spatie\Permission\Models\Role` | `config/permission.php` |
| `Domain\Identity\Models\Permission` | `Spatie\Permission\Models\Permission` | `config/permission.php` |

A matching `Domain\Identity\Database\Factories\UserFactory` produces `User` instances with Iraqi-shaped seed data. Use `User::factory()->create()` as usual. The `UserQueryBuilder` (`wherePhoneNumber()` / `getByPhoneNumber()`) is wired on the User model via `newEloquentBuilder()`.

### Schema

Migrations live under `database/migrations/` and are owned by the application. On first install they produce:

- `users` (ULID id, `huwiya_id`, name/phone/email, `locale`/`zoneinfo`/`theme`, soft deletes) + `sessions`.
- Spatie permission tables (ULID-keyed). **Teams are disabled by default**; enable in `config/permission.php` when the application needs per-tenant role scoping, then add the `haykal.permissions.team` middleware after the tenancy resolver and run `migrate:fresh`.
- Spatie Media Library table with ULID morphs.
- Laravel notifications table with a ULID morph target.

Edit, add, or drop these files directly — they're in the project tree, not inside a vendored package.

### API

- The Haykal response envelope (`success`, `code`, `message`, `data`, `errors`) is wired in. `ApiResponse::ok(...)`, `::created(...)`, `::businessError(...)`, etc. emit it.
- Every 4xx/5xx framework exception on `api/*` routes is translated into the envelope automatically by `HaykalApiServiceProvider`.
- Scramble is installed but no API modules ship with the starter. Add your first module by subclassing `HiTaqnia\Haykal\Api\ApiProvider` — for example `app/Providers/Apis/IdentityApiProvider.php` — register it in `bootstrap/providers.php`, and mount its route file from `routes/api.php`. See the [haykal-api README](../haykal-monorepo/packages/haykal-api/README.md) for the full pattern.

### Middleware

No middleware is attached to the `web` or `api` route groups by default. The starter ships with these aliases available for opt-in use:

- `haykal.permissions.team` — forwards the active tenant into Spatie's `setPermissionsTeamId()`. Slot it into the route groups or Filament panels that resolve a tenant, after the middleware that sets the active tenant. Not registered globally because not every route is tenant-scoped.

`haykal.filament.*` middlewares are available as aliases and are slotted in automatically by `BasePanel` when you add Filament panels.

### Upstream configs published

- `config/permission.php` — Spatie permission, already pointing at the application's `Domain\Identity\Models\Role` and `Domain\Identity\Models\Permission`, with `teams = false`.
- `config/media-library.php` — Spatie defaults. Swap `path_generator` for a project-specific generator when you need tenant- or subject-aware paths.
- `config/huwiya.php` — Huwiya SDK configuration.
- `config/scramble.php` — OpenAPI documentation generator.
- `config/haykal-filament-icons.php` — icon aliases (Phosphor/Tabler) merged into Filament's icon registry.

---

## Required configuration

Three things must be filled in before the application can authenticate requests. All values live in `.env`.

### 1. Huwiya IdP credentials

Obtain these from your Huwiya IdP administrator:

```dotenv
HUWIYA_URL=https://your-huwiya-instance.example
HUWIYA_PROJECT_ID=your-project-id
HUWIYA_CLIENT_ID=your-oauth-client-id
HUWIYA_CLIENT_SECRET=your-oauth-client-secret
HUWIYA_REDIRECT_URI=https://your-app.example/huwiya/callback

HUWIYA_VERIFY_SIGNATURE=true
```

The redirect URI must be registered with the IdP for this project. In production keep `HUWIYA_VERIFY_SIGNATURE=true`; disable only for isolated local testing.

### 2. Application URL and environment

```dotenv
APP_NAME="Your App"
APP_URL=https://your-app.example
APP_ENV=local|staging|production
APP_DEBUG=true|false
```

### 3. Database, cache, queue, session

The default `.env` ships with SQLite — replace with real credentials before going past local exploration. Horizon requires Redis; configure it when you wire queues.

```dotenv
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=...
```

If you use the bundled Docker development stack (next section), the Dockerized defaults in `.env.docker` already cover database, cache, queue, and session — no action needed.

---

## Local development with Docker

The starter ships a development-only Docker stack (Dev.Dockerfile + docker-compose + Makefile) that mirrors what HiTaqnia uses across its Laravel apps. It is for local development only — no production deployment artifacts are included.

### Services

| Service | Image | Purpose | Host port |
|---|---|---|---|
| `app` | `dunglas/frankenphp:1.11-php8.4-alpine` (via `deployment/Dev.Dockerfile`) | The Laravel app; runs `php artisan serve` + Vite. | 8000, 5173 |
| `horizon` | *(same app image)* | Queue worker. | — |
| `scheduler` | *(same app image)* | `php artisan schedule:run` loop. Opt-in via the `workers` compose profile. | — |
| `postgres` | `postgis/postgis:16-3.4` | Postgres 16 with PostGIS. | 5432 |
| `redis` | `redis:7-alpine` | Cache / session / queue backend. | 6379 |
| `minio` | `minio/minio` | S3-compatible object storage. | 9000 API, 9001 console |
| `minio-setup` | `minio/mc` | Creates the default `haykal` bucket on startup. | — |
| `mailpit` | `axllent/mailpit` | SMTP sink + web UI for outbound mail. | 1025 SMTP, 8025 web |

Every container / image / volume is prefixed with the `APP_SLUG` env var (default `haykal-app`). Rename it per-project with a single command (see below).

### First-time setup

Prerequisites: Docker Desktop (or any Docker Engine) and `make`.

```bash
# One command — builds images, starts services, installs deps, migrates.
make setup
```

When `make setup` finishes:

- App — `http://localhost:8000`
- Mailpit — `http://localhost:8025`
- MinIO console — `http://localhost:9001` (login: `minio` / `minio123`)
- Postgres — `localhost:5432` (database `haykal`, user/password `postgres`)

### Renaming the project

Fork the starter for a new project and every container / image / volume still reads `haykal-app-…`. Rename them in one shot:

```bash
make docker-down                          # stop the stack first
make rename-project NAME=my-new-project   # update slug everywhere
make docker-rebuild                       # rebuild images with the new tag
make setup                                # start fresh
```

`rename-project` rewrites `APP_SLUG` in `.env`, `.env.docker`, and the Makefile default. The slug must match Docker's naming rules (lowercase letters, digits, and dashes). Restart the stack afterwards so new containers pick up the new names.

### Common make commands

Full list: `make help`. The ones you will use most:

```bash
make docker-up           # Start all containers (in background)
make docker-down         # Stop everything
make docker-logs         # Tail the app container's logs
make docker-shell        # Open a shell inside the app container
make docker-psql         # Open psql against the haykal database
make docker-clean        # Down + remove volumes

make migrate             # Run pending migrations inside the container
make migrate-fresh       # Drop everything and re-migrate
make seed                # Run seeders
make test                # php artisan test inside the container
make tinker              # Open tinker
make artisan <cmd>       # Pass-through: `make artisan route:list`

make vite                # Start the Vite dev server
make build-fe            # Build production frontend assets

make composer-install    # composer install inside the container
make composer-update     # composer update inside the container
```

### Customizing ports

Every host-side port is overridable in `.env` — useful when another project is already bound to 8000 / 5432 / 6379:

```dotenv
APP_PORT=8100
VITE_PORT=5273
POSTGRES_PORT=5532
REDIS_PORT=6479
MINIO_PORT=9100
MINIO_CONSOLE_PORT=9101
MAILPIT_WEB_PORT=8125
MAILPIT_SMTP_PORT=1125
```

---

## Next steps for the developer

Everything below is app-specific — Haykal cannot choose these for you.

### 1. Define your tenant model(s)

Most HiTaqnia applications are multi-tenant. Extend `HiTaqnia\Haykal\Core\Tenancy\Models\Tenant` once per tenant type:

```php
namespace App\Models;

use HiTaqnia\Haykal\Core\Tenancy\Models\Tenant;

final class Complex extends Tenant
{
    // Add your tenant-specific columns, relations, and accessors here.
}
```

Create the migration for the tenant table yourself — Haykal does not ship one because every application's tenant schema differs. Use ULIDs for the primary key.

If the application has multiple tenant types (e.g., Agency and DevelopmentCompany), extend `Tenant` once per type, and add a `protected string $tenantForeignKey` on every tenant-owned model. See `haykal-core`'s README for the pattern.

### 2. Mark tenant-owned models with `HasTenant`

```php
namespace App\Models;

use HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Model;

final class Unit extends Model
{
    use HasTenant;

    protected string $tenantModel = Complex::class;
    // protected string $tenantForeignKey = 'complex_id'; // override when multi-type
}
```

### 3. Create your Filament panel(s)

For every panel (admin, management, residents, operations, …), subclass `HiTaqnia\Haykal\Filament\BasePanel`:

```php
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

Register the provider in `bootstrap/providers.php` (there is already a commented-out slot for it). Then scaffold the panel theme:

```bash
php artisan haykal:publish-theme management
```

The scaffolded theme `@import`s the Haykal base theme directly from the vendor directory, so shared theme updates propagate via `composer update`.

### 4. Define panel-access Gates

The `haykal.filament.access` middleware enforces a `<panel-id>.access` Gate per panel. Register one Gate per panel in `app/Providers/AppServiceProvider.php::boot()`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('management.access', fn (User $user) => $user->is_employee);
Gate::define('admin.access', fn (User $user) => $user->hasRole('admin'));
```

Apply the middleware from each panel via `HaykalPlugin::make()->withAccessChecking()` on the panel's `customizePanel()`.

### 5. Compose your own APIs

For every API module beyond Identity, subclass `HiTaqnia\Haykal\Api\ApiProvider` and register it in `bootstrap/providers.php`. Place the module's controllers under `app/Apis/<Module>/Controllers`, requests under `app/Apis/<Module>/Requests`, resources under `app/Apis/<Module>/Resources`, and the routes under `routes/api/<module>-api.php`.

See `packages/haykal-api`'s README for the complete pattern, including versioning and security schemes.

### 6. Customize the User / Role / Permission

The three identity models live in `domain/Identity/Models/` and are fully owned by the application:

- `Domain\Identity\Models\User extends Illuminate\Foundation\Auth\User` (composes `InteractsWithHuwiya`, `HasRoles`, `HasUlids`, `HasFactory`, `InteractsWithMedia`, `Notifiable`, `SoftDeletes`)
- `Domain\Identity\Models\Role extends Spatie\Permission\Models\Role` with `HasUlids`
- `Domain\Identity\Models\Permission extends Spatie\Permission\Models\Permission` with `HasUlids`

`config/auth.php` and `config/permission.php` already point at these classes.

Edit them directly — no base-class contract to respect, no Composer update to wait for. Add relations, scopes, extra fillables, and Huwiya hook overrides inline:

```php
class User extends Authenticatable implements HasMedia
{
    // ...existing traits + fillables...

    protected $fillable = [
        'name', 'phone', 'email', 'locale', 'zoneinfo', 'theme',
        'bio', 'department_id',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Override which Huwiya claims sync into local columns on every login.
    protected function attributesFromHuwiyaClaims(\Huwiya\TokenClaims $claims): array
    {
        return [
            'name' => $claims->name,
            'phone' => $claims->phone,
            'email' => $claims->email,
            'locale' => $claims->locale,
            'zoneinfo' => $claims->zoneinfo,
            'theme' => $claims->theme,
            // 'extra_column' => $claims->someClaim,
        ];
    }
}
```

When extra columns land on `users`, add a new migration (`database/migrations/xxxx_add_bio_to_users_table.php`) — the starter's base users migration is a starting point, not a published package artifact.

For phone/email recycling policy override `resolveHuwiyaConflict()` on the User; for tenant-scoped user lookups override `newHuwiyaQuery()`.

### 7. Wire queues and Horizon (when queues are used)

Once jobs are added:

```bash
php artisan vendor:publish --tag=horizon-config
php artisan vendor:publish --tag=horizon-assets
```

Configure Horizon's environments in `config/horizon.php` and protect `/horizon` with a gate in `app/Providers/HorizonServiceProvider.php`.

### 8. Localization

Create translation files under `lang/<locale>/panels/<panel-id>/resources/<resource>.php` to drive Haykal's `BaseResource` labels:

```php
return [
    'model' => ['singular' => 'Unit', 'plural' => 'Units'],
    'navigation' => ['label' => 'Units', 'group' => 'Property'],
];
```

Haykal reads these keys automatically on every resource that extends `BaseResource`.

---

## Running the application

Two supported paths — pick whichever matches your workflow.

### Docker (recommended)

```bash
make setup        # first run only
make docker-up    # subsequent sessions
```

See [Local development with Docker](#local-development-with-docker) for the full command surface.

### Without Docker

```bash
npm install
composer dev
```

`composer dev` runs `php artisan serve`, the queue listener, Pail (log tail), and Vite concurrently. Redis, Postgres, and MinIO are your responsibility when running this way — update `.env` accordingly.

Quick sanity check once the server is up:

```bash
# Any panel you have registered redirects unauthenticated users to Huwiya.
curl -i http://localhost:8000/<your-panel-id>
```

API endpoints and their Scramble docs are added by subclassing `HiTaqnia\Haykal\Api\ApiProvider` per module — the starter does not ship any. See [haykal-api](../haykal-monorepo/packages/haykal-api/README.md) for the pattern.

---

## Reference

| Question | Where to look |
|---|---|
| Exposing new API modules, versioning, conventions for controllers/requests/resources | `../haykal/packages/haykal-api/README.md` |
| Panel base class, tenancy, translatable forms, icons, theme | `../haykal/packages/haykal-filament/README.md` |
| Result pattern, tenancy, User hooks, Huwiya claim sync, multi-tenant types | `../haykal/packages/haykal-core/README.md` |
| Authentication flow, guards, hooks, testing, error codes | `../huwiya/packages/huwiya-laravel/docs/` |

---

## License

Proprietary. Property of HiTaqnia.
