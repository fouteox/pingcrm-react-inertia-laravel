#!/usr/bin/env sh
set -e

container_mode=${CONTAINER_MODE:-"http"}

getDatabaseType() {
    if [ -f /app/.env.production ]; then
        grep "^DB_CONNECTION=" /app/.env.production | cut -d '=' -f2
    fi
}

initialStuff() {
    echo "Container mode: $container_mode"

    db_type=$(getDatabaseType)
    if [ "$db_type" = "sqlite" ]; then
        [ ! -f /app/.infrastructure/database.sqlite ] && touch /app/.infrastructure/database.sqlite
    fi

    php artisan storage:link
}

optimizeApp() {
    php artisan optimize:clear
    php artisan optimize
}

startService() {
    [ "$1" = "http" ] && php artisan migrate:fresh --seed --force

    optimizeApp
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
