ARG PHP_VERSION=8.5

############################################
# Base Stage
############################################
FROM serversideup/php:${PHP_VERSION}-frankenphp AS base

USER root

RUN install-php-extensions bcmath intl

############################################
# Development Image
############################################
FROM base AS development

# We can pass USER_ID and GROUP_ID as build arguments
# to ensure the www-data user has the same UID and GID
# as the user running Docker.
ARG USER_ID
ARG GROUP_ID

# Switch to root so we can set the user ID and group ID
USER root

# Installer Bun pour le développement frontend
COPY --from=oven/bun:1.3-debian /usr/local/bin/bun /usr/local/bin/bun

# Set the user ID and group ID for www-data
RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID  && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID

# Drop privileges back to www-data
USER www-data

############################################
# Builder Stage (vendor only — JS assets are pre-built on the CI runner
# against a real MariaDB so Wayfinder generates types that match production)
############################################
FROM base AS builder

COPY --link composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit

COPY --link . .

RUN composer dump-autoload --classmap-authoritative --no-dev

############################################
# App Image (also runs SSR via `php artisan inertia:start-ssr --runtime=bun`)
############################################
FROM base AS app

COPY --from=oven/bun:1.3-debian /usr/local/bin/bun /usr/local/bin/bun

COPY --link --chown=33:33 --from=builder /var/www/html/vendor ./vendor

COPY --link --chown=33:33 . .

RUN mkdir -p \
    storage/logs \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
