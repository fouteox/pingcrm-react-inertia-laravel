ARG PHP_VERSION=8.4

FROM fouteox/laravel-php-base:${PHP_VERSION} AS base

ARG WWWUSER=1000
ARG WWWGROUP=1000

ENV USER=www-data \
    ROOT=/app

RUN userdel --remove --force www-data \
    && groupadd --force -g ${WWWGROUP} ${USER} \
    && useradd -ms /bin/bash --no-log-init --no-user-group -g ${WWWGROUP} -u ${WWWUSER} ${USER} \
    && chown -R ${USER}:${USER} ${ROOT} /var/{log,run}

COPY --link --chmod=755 deployment/start-container /usr/local/bin/docker-entrypoint

USER ${USER}

COPY --chown=${USER}:${USER} deployment/supervisord.conf /etc/
COPY --chown=${USER}:${USER} deployment/supervisord.*.conf /etc/supervisor/conf.d/

###########################################

FROM base AS common

COPY --link --chown=${WWWUSER}:${WWWGROUP} composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-ansi \
    --no-scripts \
    --audit

###########################################
# Build frontend assets
###########################################

FROM common AS build

COPY --link --chown=${WWWUSER}:${WWWGROUP} package*.json bun.lock* ./

RUN bun install --frozen-lockfile

COPY --link --chown=${WWWUSER}:${WWWGROUP} . .

RUN bun run build:ssr

###########################################

FROM common AS prod

COPY --link --chown=${WWWUSER}:${WWWGROUP} . .
COPY --link --chown=${WWWUSER}:${WWWGROUP} --from=build ${ROOT}/public public
COPY --link --chown=${WWWUSER}:${WWWGROUP} --from=build ${ROOT}/bootstrap/ssr bootstrap/ssr
COPY --link --chown=${WWWUSER}:${WWWGROUP} --from=build ${ROOT}/node_modules node_modules

RUN mkdir -p ${ROOT}/storage/framework/{sessions,views,cache,testing} ${ROOT}/storage/logs ${ROOT}/bootstrap/cache \
    && chmod -R a+rw ${ROOT}/storage ${ROOT}/bootstrap/cache

RUN composer dump-autoload --classmap-authoritative --no-dev --no-scripts \
    && composer clear-cache
