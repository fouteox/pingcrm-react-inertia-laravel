#!/usr/bin/env sh
set -e

container_mode=${CONTAINER_MODE:-"http"}

getDatabaseType() {
    if [ -f /app/.env.production ]; then
        grep "^DB_CONNECTION=" /app/.env.production | cut -d '=' -f2
    fi
}

startService() {
    echo "Container mode: $1"

    if [ "$1" = "http" ]; then
        db_type=$(getDatabaseType)
        if [ "$db_type" = "sqlite" ]; then
            [ ! -f /app/.infrastructure/database.sqlite ] && touch /app/.infrastructure/database.sqlite
        fi

        php artisan storage:link
        php artisan migrate:fresh --seed --force
        php artisan optimize:clear
        php artisan optimize
    fi

    exec /usr/bin/supervisord -c "/etc/supervisor/conf.d/supervisord.$1.conf"
}

if [ "$1" != "" ]; then
    exec docker-php-entrypoint "$@"
elif [ "${container_mode}" = "http" ] || \
     [ "${container_mode}" = "horizon" ] || \
     [ "${container_mode}" = "reverb" ] || \
     [ "${container_mode}" = "scheduler" ]; then
    startService "${container_mode}"
else
    echo "Container mode mismatched."
    exit 1
fi
