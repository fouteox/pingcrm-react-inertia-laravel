ARG PHP_VERSION=8.4
ARG FRANKENPHP_VERSION=1.4
ARG BUN_VERSION="latest"
ARG APP_ENV=production
ARG NODE_VERSION=22

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION} AS base

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG APP_DIR=/app
ARG SUPERCRONIC_VERSION=0.2.33
ARG APP_ENV=production
ARG TARGETARCH=x86_64
ARG NODE_VERSION

ENV DEBIAN_FRONTEND=noninteractive \
    OCTANE_SERVER=frankenphp \
    USER=www-data \
    ROOT=${APP_DIR} \
    APP_ENV=${APP_ENV} \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=24 \
    SERVER_NAME=:80 \
    PATH=/usr/local/node/bin:$PATH \
    XDG_CONFIG_HOME=${APP_DIR}/.config \
    XDG_DATA_HOME=${APP_DIR}/.data

WORKDIR ${ROOT}

SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

RUN apt-get update && apt-get upgrade -yqq && apt-get install -yqq --no-install-recommends \
    git \
    ca-certificates \
    supervisor \
    curl \
    && install-php-extensions \
        @composer \
        pcntl \
        bcmath \
        sockets \
        intl \
        opcache \
        zip \
        gd \
        pdo_mysql \
        redis \
    && curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x -o nodesource_setup.sh \
    && bash nodesource_setup.sh \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && rm nodesource_setup.sh \
    && case "${TARGETARCH}" in \
        arm64) _cronic_fname='supercronic-linux-arm64' ;; \
        amd64) _cronic_fname='supercronic-linux-amd64' ;; \
        *) echo >&2 "error: unsupported architecture: ${TARGETARCH}"; exit 1 ;; \
    esac \
    && curl -fsSL "https://github.com/aptible/supercronic/releases/download/v${SUPERCRONIC_VERSION}/${_cronic_fname}" -o /usr/bin/supercronic \
    && chmod +x /usr/bin/supercronic \
    && mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel \
    && apt-get -y autoremove \
    && apt-get clean \
    && docker-php-source delete \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && rm -f /var/log/lastlog /var/log/faillog

RUN userdel --remove --force www-data \
    && groupadd --force -g ${WWWGROUP} ${USER} \
    && useradd -ms /bin/bash --no-log-init --no-user-group -g ${WWWGROUP} -u ${WWWUSER} ${USER} \
    && setcap -r /usr/local/bin/frankenphp \
    && chown -R ${USER}:${USER} ${ROOT} /var/{log,run} \
    && chmod -R a+rw ${ROOT} /var/{log,run}

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

USER ${USER}

COPY --link --chown=${WWWUSER}:${WWWUSER} deployment/supervisord.conf /etc/
COPY --link --chown=${WWWUSER}:${WWWUSER} deployment/octane/FrankenPHP/supervisord.http.conf /etc/supervisor/conf.d/
COPY --link --chown=${WWWUSER}:${WWWUSER} deployment/supervisord.*.conf /etc/supervisor/conf.d/
COPY --link --chmod=755 --chown=${WWWUSER}:${WWWUSER} deployment/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link --chmod=755 --chown=${WWWUSER}:${WWWUSER} deployment/healthcheck /usr/local/bin/healthcheck
COPY --link --chown=${WWWUSER}:${WWWUSER} deployment/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=5s --interval=2s --timeout=5s --retries=8 CMD healthcheck || exit 1

###########################################

FROM base AS common

USER ${USER}

COPY --link --chown=${WWWUSER}:${WWWUSER} composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit

###########################################
# Build frontend assets with Bun
###########################################

FROM oven/bun:${BUN_VERSION} AS build

ARG APP_ENV=production

ENV ROOT=/app \
    APP_ENV=${APP_ENV} \
    NODE_ENV=${APP_ENV:-production}

WORKDIR ${ROOT}

COPY --link package.json bun.lock* ./

RUN bun install --frozen-lockfile

COPY --link . .
COPY --link --from=common ${ROOT}/vendor vendor

RUN bun run build

###########################################

FROM common AS prod

USER ${USER}

COPY --link --chown=${WWWUSER}:${WWWUSER} . .
COPY --link --chown=${WWWUSER}:${WWWUSER} --from=build ${ROOT}/public public
COPY --link --chown=${WWWUSER}:${WWWUSER} --from=build ${ROOT}/bootstrap/ssr bootstrap/ssr
COPY --link --chown=${WWWUSER}:${WWWUSER} --from=build ${ROOT}/node_modules node_modules

RUN mkdir -p ${ROOT}/.infrastructure \
    && mkdir -p ${ROOT}/storage/framework/{sessions,views,cache,testing} ${ROOT}/storage/logs ${ROOT}/bootstrap/cache \
    && chmod -R a+rw ${ROOT}/.infrastructure ${ROOT}/storage ${ROOT}/bootstrap/cache

RUN composer install \
    --classmap-authoritative \
    --no-interaction \
    --no-ansi \
    --no-dev \
    && composer clear-cache
