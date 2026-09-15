#!/bin/sh

set -eu

# Laravel's .env is mounted into the app container. Load only project-local
# test connection settings and override every database variable for this process.
set -a
. ./.env
set +a

export APP_ENV=testing
export DB_CONNECTION=mysql
export DB_HOST="${DB_TEST_HOST:?DB_TEST_HOST must be set}"
export DB_PORT="${DB_TEST_PORT:?DB_TEST_PORT must be set}"
export DB_DATABASE="${DB_TEST_DATABASE:?DB_TEST_DATABASE must be set}"
export DB_USERNAME="${DB_TEST_USERNAME:?DB_TEST_USERNAME must be set}"
export DB_PASSWORD="${DB_TEST_PASSWORD:?DB_TEST_PASSWORD must be set}"

if [ "${1:-}" = "artisan" ]; then
    shift
    exec php artisan "$@"
fi

exec php artisan test "$@"
