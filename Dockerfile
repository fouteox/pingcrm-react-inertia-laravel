ARG PHP_VERSION=8.4
ARG BUN_VERSION=1.2.4

FROM fouteox/laravel-php-base:${PHP_VERSION} AS base

ARG WWWUSER=1000
ARG WWWGROUP=1000

ENV USER=www-data \
    ROOT=/app

RUN userdel --remove --force www-data \
    && groupadd --force -g ${WWWGROUP} ${USER} \
    && useradd -ms /bin/bash --no-log-init --no-user-group -g ${WWWGROUP} -u ${WWWUSER} ${USER} \
    && chown -R ${USER}:${USER} ${ROOT} /var/{log,run}

USER ${USER}

COPY --chown=${USER}:${USER} deployment/supervisord.conf /etc/
COPY --chown=${USER}:${USER} deployment/supervisord.*.conf /etc/supervisor/conf.d/

###########################################

FROM base AS common

USER ${USER}

COPY --link --chown=${WWWUSER}:${WWWGROUP} composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit

###########################################
# Build frontend assets
###########################################

FROM oven/bun:${BUN_VERSION} AS build

ENV ROOT=/app

WORKDIR ${ROOT}

COPY --link package.json bun.lock* ./

RUN bun install --frozen-lockfile

COPY --link . .
COPY --link --from=common ${ROOT}/vendor vendor

RUN bun run build:ssr

###########################################

FROM common AS prod

USER ${USER}

COPY --link --chown=${WWWUSER}:${WWWGROUP} . .
COPY --link --chown=${WWWUSER}:${WWWGROUP} --from=build ${ROOT}/public public
COPY --link --chown=${WWWUSER}:${WWWGROUP} --from=build ${ROOT}/bootstrap/ssr bootstrap/ssr
COPY --link --chown=${WWWUSER}:${WWWGROUP} --from=build ${ROOT}/node_modules node_modules

RUN mkdir -p ${ROOT}/storage/framework/{sessions,views,cache,testing} ${ROOT}/storage/logs ${ROOT}/bootstrap/cache \
    && chmod -R a+rw ${ROOT}/storage ${ROOT}/bootstrap/cache

RUN composer install \
    --classmap-authoritative \
    --no-interaction \
    --no-ansi \
    --no-dev \
    && composer clear-cache
