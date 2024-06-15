#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'artisan' ]; then
	# Install the project the first time PHP is started
	# After the installation, the following block can be deleted
	if [ ! -f composer.json ]; then
		rm -Rf tmp/
		composer create-project "laravel/laravel $LARAVEL_VERSION" tmp --stability="$STABILITY" --remove-vcs --no-scripts --prefer-dist --no-progress --no-interaction --no-install

		cd tmp
		cp -Rp . ..
		cd -
		rm -Rf tmp/

		composer require "php:>=$PHP_VERSION" --no-scripts --no-install
	fi

    if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
        composer run-script post-root-package-install
        composer install --prefer-dist --no-progress --no-interaction
        composer run-script post-create-project-cmd
    fi

    if [ "$APP_ENV" = 'demo' ]; then
        setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX storage
        setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX storage
        setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX bootstrap/cache
        setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX bootstrap/cache

	    if [ ! -f .env ]; then
            cp /app/.env.prod /app/.env
            composer run-script post-create-project-cmd
            php artisan db:seed --force
        fi
        php artisan optimize;
    fi

    service cron start
fi

exec docker-php-entrypoint "$@"
