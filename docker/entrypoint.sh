#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is missing. Run ./docker/setup.sh and use --env-file .env.docker." >&2
    exit 1
fi

mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

exec docker-php-entrypoint "$@"
