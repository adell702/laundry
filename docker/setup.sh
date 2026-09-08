#!/bin/sh
set -eu

cd "$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"

if [ -f .env.docker ]; then
    echo ".env.docker already exists; keeping its credentials unchanged."
else
    command -v openssl >/dev/null 2>&1 || {
        echo "OpenSSL is required to generate local credentials." >&2
        exit 1
    }

    umask 077
    app_key="$(openssl rand -base64 32)"
    db_password="$(openssl rand -hex 24)"
    root_password="$(openssl rand -hex 24)"

    # Generated values use base64/hex alphabets, which are safe in these replacements.
    sed -e "s|^APP_KEY=$|APP_KEY=base64:$app_key|" \
        -e "s|^DB_PASSWORD=$|DB_PASSWORD=$db_password|" \
        -e "s|^MYSQL_ROOT_PASSWORD=$|MYSQL_ROOT_PASSWORD=$root_password|" \
        .env.docker.example > .env.docker

    echo "Created .env.docker with a new application key and database passwords."
fi

echo "Start the app: docker compose --env-file .env.docker up -d --build --wait"
