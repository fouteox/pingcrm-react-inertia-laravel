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
# Builder Stage
############################################
FROM base AS builder

COPY --from=oven/bun:1.3-debian /usr/local/bin/bun /usr/local/bin/bun

COPY --link composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit

COPY --link package.json bun.lock* ./

RUN bun install --frozen-lockfile

COPY --link . .

RUN composer dump-autoload --classmap-authoritative --no-dev

ARG ENV_HASH
RUN --mount=type=secret,id=dotenv \
    echo "Build with ENV_HASH=${ENV_HASH}" && \
    set -a && . /run/secrets/dotenv && set +a && \
    bun run build:ssr

############################################
# App Image
############################################
FROM base AS app

COPY --link --chown=33:33 --from=builder /var/www/html/vendor ./vendor

COPY --link --chown=33:33 . .

COPY --link --chown=33:33 --from=builder /var/www/html/public/build ./public/build

RUN mkdir -p \
    storage/logs \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

############################################
# SSR Image
############################################
FROM oven/bun:1.3-debian AS ssr

WORKDIR /app

COPY --from=builder /var/www/html/bootstrap/ssr ./bootstrap/ssr

EXPOSE 13714

CMD ["bun", "bootstrap/ssr/ssr.js"]
