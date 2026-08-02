#!/usr/bin/env sh
set -eu

: "${PORT:=10000}"
: "${RUN_MIGRATIONS:=true}"
: "${RUN_SEEDERS:=true}"
: "${MIGRATION_ATTEMPTS:=5}"
: "${MIGRATION_RETRY_SECONDS:=5}"

database_url="${DATABASE_URL:-${DB_URL:-}}"

if [ -n "$database_url" ]; then
    case "$database_url" in
        postgres://*|postgresql://*|pgsql://*)
            export DB_CONNECTION=pgsql
            ;;
        mysql://*)
            export DB_CONNECTION=mysql
            ;;
        mariadb://*)
            export DB_CONNECTION=mariadb
            ;;
    esac
else
    case "${DB_CONNECTION:-}" in
        postgres|postgresql)
            export DB_CONNECTION=pgsql
            ;;
    esac
fi

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required. Generate one with: php artisan key:generate --show"
    exit 1
fi

sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

php artisan storage:link || true
php artisan package:discover --ansi || true

run_with_retries() {
    command_label="$1"
    shift
    attempt=1

    while ! "$@"; do
        if [ "$attempt" -ge "$MIGRATION_ATTEMPTS" ]; then
            echo "${command_label} failed after ${attempt} attempt(s)."
            exit 1
        fi

        echo "${command_label} failed on attempt ${attempt}; retrying in ${MIGRATION_RETRY_SECONDS}s..."
        attempt=$((attempt + 1))
        sleep "$MIGRATION_RETRY_SECONDS"
    done
}

if [ "$RUN_MIGRATIONS" = "true" ]; then
    run_with_retries "Database migrations" php artisan migrate --force
fi

if [ "$RUN_SEEDERS" = "true" ]; then
    run_with_retries "Database seeding" php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
