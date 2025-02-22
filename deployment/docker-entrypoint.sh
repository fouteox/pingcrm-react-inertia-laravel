#!/usr/bin/env sh
set -e

container_mode=${CONTAINER_MODE:-"http"}

initialStuff() {
    echo "Container mode: $container_mode"

    if [ ! -f /app/.infrastructure/database.sqlite ]; then
        touch /app/.infrastructure/database.sqlite
    fi

    php artisan storage:link
}

optimizeApp() {
    php artisan optimize:clear
    php artisan optimize
}

startHttp() {
    php artisan migrate:fresh --seed --force
    optimizeApp
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.frankenphp.conf
}

startScheduler() {
    optimizeApp
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.scheduler.conf
}

if [ "$1" != "" ]; then
    exec docker-php-entrypoint "$@"
elif [ "${container_mode}" = "http" ]; then
    initialStuff
    startHttp
elif [ "${container_mode}" = "scheduler" ]; then
    initialStuff
    startScheduler
else
    echo "Container mode mismatched."
    exit 1
fi
