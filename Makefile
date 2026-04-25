# =============================================================================
# Haykal Starter — Local Development Commands
# =============================================================================
# Usage: make <command>

.PHONY: help setup rename-project \
        docker-build docker-up docker-down docker-restart docker-logs docker-logs-all docker-shell docker-psql docker-clean docker-rebuild \
        migrate migrate-down migrate-fresh seed \
        test tinker artisan \
        vite build-fe \
        composer-install composer-update

# Project slug read from the environment. Falls back to `haykal-app` — the
# docker-compose defaults mirror this. Use `make rename-project NAME=...`
# to change it consistently across .env.docker and this file.
APP_SLUG ?= haykal-app

# Every docker-compose invocation reads .env.docker (both for variable
# interpolation in docker-compose.yaml and for the env loaded into each
# service). The local .env file is intentionally left untouched so it can
# host a separate non-Docker config — e.g. sqlite for host-side artisan.
DC := APP_SLUG=$(APP_SLUG) docker compose --env-file .env.docker

# =============================================================================
# Help
# =============================================================================

help:
	@echo "Haykal Starter — Local Development"
	@echo ""
	@echo "Setup:"
	@echo "  make setup                    - First-time docker setup (reads .env.docker; build, install deps, migrate)"
	@echo "  make rename-project NAME=...  - Rename every container / image / volume prefix"
	@echo ""
	@echo "Docker:"
	@echo "  make docker-build             - Build Docker images"
	@echo "  make docker-up                - Start all containers"
	@echo "  make docker-down              - Stop all containers"
	@echo "  make docker-restart           - Restart all containers"
	@echo "  make docker-logs              - View app container logs"
	@echo "  make docker-logs-all          - View all container logs"
	@echo "  make docker-shell             - Open shell in app container"
	@echo "  make docker-psql              - Open PostgreSQL shell"
	@echo "  make docker-clean             - Remove all containers and volumes"
	@echo "  make docker-rebuild           - Rebuild from scratch"
	@echo ""
	@echo "Database:"
	@echo "  make migrate                  - Run database migrations"
	@echo "  make migrate-down             - Rollback last migration"
	@echo "  make migrate-fresh            - Drop all tables and re-run migrations"
	@echo "  make seed                     - Run database seeders"
	@echo ""
	@echo "Laravel:"
	@echo "  make test                     - Run tests"
	@echo "  make tinker                   - Open Laravel tinker"
	@echo "  make artisan <cmd>            - Run artisan command"
	@echo ""
	@echo "Frontend:"
	@echo "  make vite                     - Run Vite dev server"
	@echo "  make build-fe                 - Build frontend assets"
	@echo ""
	@echo "Composer:"
	@echo "  make composer-install         - Install PHP dependencies"
	@echo "  make composer-update          - Update PHP dependencies"

# =============================================================================
# Rename Project
# =============================================================================
# Rewrites APP_SLUG across .env.docker and this Makefile so every
# container / image / volume name is prefixed with the new slug. Run once
# after cloning the starter; re-run whenever the project is forked.
#
#     make rename-project NAME=my-new-project
#
# The new slug must be lowercase, dashes-only — the same shape Docker accepts.
# =============================================================================

rename-project:
	@if [ -z "$(NAME)" ]; then \
		echo "Usage: make rename-project NAME=my-new-project"; \
		exit 1; \
	fi
	@if ! echo "$(NAME)" | grep -Eq '^[a-z][a-z0-9-]*$$'; then \
		echo "Error: NAME must be lowercase letters, digits, and dashes only (e.g. my-new-project)."; \
		exit 1; \
	fi
	@if docker compose ps -q 2>/dev/null | grep -q .; then \
		echo "Error: stop the stack first with 'make docker-down' before renaming."; \
		exit 1; \
	fi
	@echo "Renaming project slug: $(APP_SLUG) -> $(NAME)"
	@# .env.docker — set or add APP_SLUG=
	@if [ -f .env.docker ]; then \
		if grep -q '^APP_SLUG=' .env.docker; then \
			sed -i.bak "s/^APP_SLUG=.*/APP_SLUG=$(NAME)/" .env.docker && rm -f .env.docker.bak; \
		else \
			printf '\nAPP_SLUG=$(NAME)\n' >> .env.docker; \
		fi; \
		echo "  updated .env.docker"; \
	fi
	@# Makefile — bump the default so `make` without env still picks up the new slug
	@sed -i.bak "s/^APP_SLUG ?= .*/APP_SLUG ?= $(NAME)/" Makefile && rm -f Makefile.bak
	@echo "  updated Makefile default"
	@echo ""
	@echo "Done. Next: 'make docker-rebuild' (images must be rebuilt to adopt the new tag)."

# =============================================================================
# Setup
# =============================================================================

setup:
	@echo "Setting up Docker development environment for $(APP_SLUG)..."
	@echo "(reads .env.docker — your local .env is left untouched)"
	$(DC) build
	$(DC) up -d postgres redis minio minio-setup mailpit
	@echo "Waiting for services to be ready..."
	@sleep 10
	@echo "Installing PHP dependencies..."
	$(DC) run --rm --user root app composer install
	@echo "Installing JS dependencies..."
	$(DC) run --rm --user root app bun install
	@echo "Setting permissions..."
	$(DC) run --rm --user root app chown -R laravel:laravel /var/www/html/vendor /var/www/html/node_modules
	@echo "Creating storage link..."
	$(DC) run --rm app php artisan storage:link
	@echo "Running migrations..."
	$(DC) run --rm app php artisan migrate --force
	@echo "Starting all services..."
	$(DC) up -d
	@echo ""
	@echo "Setup complete!"
	@echo ""
	@echo "Access points:"
	@echo "  App:     http://localhost:8000"
	@echo "  Mailpit: http://localhost:8025"
	@echo "  MinIO:   http://localhost:9001 (minio/minio123)"

# =============================================================================
# Docker
# =============================================================================

docker-build:
	$(DC) build

docker-up:
	$(DC) up -d

docker-down:
	$(DC) down

docker-restart:
	$(DC) restart

docker-logs:
	$(DC) logs -f app

docker-logs-all:
	$(DC) logs -f

docker-shell:
	$(DC) exec app sh

docker-psql:
	$(DC) exec postgres psql -U postgres -d $${POSTGRES_DB:-haykal}

docker-clean:
	$(DC) down -v --remove-orphans
	@echo "Cleaned up all containers and volumes"

docker-rebuild:
	$(DC) down -v --remove-orphans
	$(DC) build --no-cache
	@echo "Rebuild complete. Run 'make setup' to set up the environment."

# =============================================================================
# Database
# =============================================================================

migrate:
	$(DC) exec app php artisan migrate

migrate-down:
	$(DC) exec app php artisan migrate:rollback

migrate-fresh:
	$(DC) exec app php artisan migrate:fresh

seed:
	$(DC) exec app php artisan db:seed

# =============================================================================
# Laravel
# =============================================================================

test:
	$(DC) exec app php artisan test

tinker:
	$(DC) exec app php artisan tinker

artisan:
	$(DC) exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

# =============================================================================
# Frontend
# =============================================================================

vite:
	$(DC) exec app bun run dev

build-fe:
	$(DC) exec app bun run build

# =============================================================================
# Composer
# =============================================================================

composer-install:
	$(DC) run --rm --user root app composer install
	$(DC) run --rm --user root app chown -R laravel:laravel /var/www/html/vendor

composer-update:
	$(DC) run --rm --user root app composer update
	$(DC) run --rm --user root app chown -R laravel:laravel /var/www/html/vendor

# Catch-all for artisan arguments
%:
	@:
