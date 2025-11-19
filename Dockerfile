ARG PHP_VERSION=8.4

############################################
# Base Stage
############################################
FROM serversideup/php:${PHP_VERSION}-frankenphp AS base

USER root

RUN install-php-extensions bcmath

############################################
# Builder Stage
############################################
FROM base AS builder

COPY --from=oven/bun:1 /usr/local/bin/bun /usr/local/bin/bun

COPY --link composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit

COPY --link package*.json ./

RUN bun install --frozen-lockfile

COPY --link . .

RUN composer dump-autoload --classmap-authoritative --no-dev

RUN bun run build:ssr

############################################
# App Image
############################################
FROM base AS app

COPY --link --chown=33:33 --from=builder /var/www/html/vendor ./vendor

COPY --link --chown=33:33 . .

COPY --link --chown=33:33 --from=builder /var/www/html/public/build ./public/build

COPY --chmod=755 ./docker/entrypoint.d/ /etc/entrypoint.d/

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
FROM node:24-alpine AS ssr

WORKDIR /app

COPY --from=builder /var/www/html/bootstrap/ssr ./bootstrap/ssr

COPY --from=builder /var/www/html/node_modules ./node_modules

EXPOSE 13714

CMD ["node", "bootstrap/ssr/ssr.js"]
