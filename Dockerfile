ARG PHP_VERSION=8.5

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

COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node:24-bookworm-slim /usr/local/include/node /usr/local/include/node

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm && \
    ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY --link composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit

COPY --link package*.json ./

RUN npm ci

COPY --link . .

RUN composer dump-autoload --classmap-authoritative --no-dev

ARG ENV_HASH
RUN --mount=type=secret,id=dotenv \
    echo "Build with ENV_HASH=${ENV_HASH}" && \
    set -a && . /run/secrets/dotenv && set +a && \
    npm run build:ssr

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
FROM node:24-bookworm-slim AS ssr

WORKDIR /app

COPY --from=builder /var/www/html/bootstrap/ssr ./bootstrap/ssr

COPY --from=builder /var/www/html/node_modules ./node_modules

EXPOSE 13714

CMD ["node", "bootstrap/ssr/ssr.js"]
