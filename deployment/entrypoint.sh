#!/bin/sh
# Container entrypoint for the Haykal Starter dev image.
#
# Ensures APP_KEY exists before handing off to the container's main command.
# The key is generated once and persisted to .env.docker (the env_file the
# compose stack loads) so it survives container restarts and is shared by
# every service that mounts the project root.

set -eu

ENV_FILE="${ROOT:-/var/www/html}/.env.docker"

if [ -f "$ENV_FILE" ]; then
    current_key=$(grep -E '^APP_KEY=' "$ENV_FILE" | head -n1 | cut -d= -f2-)
    if [ -z "$current_key" ]; then
        echo "[entrypoint] APP_KEY is empty — generating one into .env.docker"
        generated=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")
        # macOS-compatible in-place replace using a temp file.
        tmp=$(mktemp)
        awk -v key="APP_KEY=$generated" '
            BEGIN { replaced = 0 }
            /^APP_KEY=/ { print key; replaced = 1; next }
            { print }
            END { if (!replaced) print key }
        ' "$ENV_FILE" > "$tmp" && mv "$tmp" "$ENV_FILE"
        export APP_KEY="$generated"
    fi
fi

exec "$@"
