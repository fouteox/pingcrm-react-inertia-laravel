#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ]; then
	# Install the project the first time PHP is started
	# After the installation, the following block can be deleted
	if [ ! -f composer.json ]; then
		rm -Rf tmp/
		composer create-project "laravel/laravel $LARAVEL_VERSION" tmp --stability="$STABILITY" --prefer-dist --no-progress --no-interaction

		cd tmp
		cp -Rp . ..
		cd -
		rm -Rf tmp/
	fi

	if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
    		composer install --prefer-dist --no-progress --no-interaction
    fi

    mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        storage/framework/testing \
        storage/logs \
        bootstrap/cache && chmod -R a+rw storage

    if [ ! -f .env ]; then
        cp .env.example .env
        php artisan key:generate
        php artisan storage:link
        php artisan migrate --force
        php artisan db:seed --force
    fi
fi

exec docker-php-entrypoint "$@"
