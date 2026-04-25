# Haykal Starter

A Laravel 13 starter pre-wired with the [Haykal](https://github.com/hitaqnia/haykal) suite — Huwiya authentication, Filament-ready base, REST API envelope, Spatie permissions, and a complete Docker dev stack. Clone it as the starting point for any new HiTaqnia project.

## Quick start

Prerequisites: Docker Desktop (or any Docker Engine) and `make`.

```bash
make setup        # build images, install deps, migrate
make docker-up    # subsequent sessions
```

Once running:

- App — http://localhost:8000
- Mailpit — http://localhost:8025
- MinIO console — http://localhost:9001 (`minio` / `minio123`)
- Postgres — `localhost:5432` (db `haykal`, user/pass `postgres`)

## Renaming the project

Every container, image, and volume is prefixed with `APP_SLUG` (default `haykal-app`). Rename in one shot:

```bash
make docker-down
make rename-project NAME=my-new-project
make docker-rebuild
make setup
```

## Common commands

```bash
make docker-shell        # shell into the app container
make docker-logs         # tail app logs
make docker-psql         # psql into the database

make migrate             # run migrations
make migrate-fresh       # drop everything and re-migrate
make seed                # run seeders
make test                # php artisan test
make tinker              # php artisan tinker
make artisan <cmd>       # passthrough, e.g. make artisan route:list

make vite                # vite dev server
make build-fe            # production frontend build
```

Full list: `make help`.

## Configuration

Edit `.env.docker` for the Dockerized stack (or `.env` for non-Docker). The two things you must fill in before the app can authenticate:

```dotenv
HUWIYA_URL=https://your-huwiya-instance.example
HUWIYA_PROJECT_ID=...
HUWIYA_CLIENT_ID=...
HUWIYA_CLIENT_SECRET=...
HUWIYA_REDIRECT_URI=https://your-app.example/huwiya/callback
```

`APP_KEY` is generated automatically by the container entrypoint on first start.

Host ports are overridable in `.env.docker` — useful when 8000/5432/6379 are already taken:

```dotenv
APP_PORT=8100
POSTGRES_PORT=5532
REDIS_PORT=6479
```

## What's wired up

- **Auth** — Huwiya guards (`huwiya-web`, `huwiya-api`) wired in `config/auth.php`. The `User`, `Role`, and `Permission` models live in `domain/Identity/Models/` and are owned by the app — edit them directly.
- **Schema** — Migrations under `database/migrations/` ship with `users`, `sessions`, Spatie permission tables (ULID-keyed, teams disabled), Media Library, and notifications.
- **API** — Haykal response envelope is registered. Add modules by subclassing `HiTaqnia\Haykal\Api\ApiProvider`. No modules ship by default.
- **Filament** — `BasePanel` is available. No panels ship by default. Register them in `bootstrap/providers.php`.
- **Middleware** — `haykal.permissions.team` alias available for tenant-scoped routes (not registered globally).

## Project layout

```
app/                 Laravel framework glue (providers, HTTP, Filament).
domain/              DDD bounded contexts. Identity ships pre-wired.
support/             App-wide utilities. Empty — add as needed.
packages/            In-tree path repositories. Empty.
deployment/          Dockerfile, php.ini, container entrypoint.
docker-compose.yaml  app + horizon + scheduler + postgres + redis + minio + mailpit.
Makefile             setup / docker-* / migrate / artisan / rename-project.
```

Autoload roots: `App\` → `app/`, `Domain\` → `domain/`, `Support\` → `support/`.

## Next steps

When you're ready to build the actual app:

1. Define your tenant model(s) by extending `HiTaqnia\Haykal\Core\Tenancy\Models\Tenant`.
2. Mark tenant-owned models with the `HasTenant` trait.
3. Create Filament panels by subclassing `HiTaqnia\Haykal\Filament\BasePanel` and registering them in `bootstrap/providers.php`.
4. Define `<panel-id>.access` Gates in `AppServiceProvider::boot()`.
5. Add API modules by subclassing `HiTaqnia\Haykal\Api\ApiProvider`.
6. Customize `domain/Identity/Models/User.php` — add columns, relations, and Huwiya claim hooks inline.

## Documentation

| Package | Repository |
|---|---|
| `haykal` (metapackage) | https://github.com/hitaqnia/haykal |
| `haykal-core` (tenancy, permissions, result pattern) | https://github.com/hitaqnia/haykal-core |
| `haykal-api` (REST conventions, envelope, Scramble) | https://github.com/hitaqnia/haykal-api |
| `haykal-filament` (BasePanel, BaseResource, themes) | https://github.com/hitaqnia/haykal-filament |

## License

Proprietary. Property of HiTaqnia.
