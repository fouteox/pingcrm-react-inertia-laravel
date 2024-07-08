#syntax=docker/dockerfile:1.4

# Versions
FROM dunglas/frankenphp:1-php8.3 AS frankenphp_upstream

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target

# Base FrankenPHP image
FROM frankenphp_upstream AS frankenphp_base

ARG USER=www-data
ARG UID
ARG GID

RUN groupmod -g ${GID} ${USER} && \
    usermod -u ${UID} -g ${GID} ${USER} && \
    setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp && \
    chown -R ${UID}:${GID} /data/caddy /var/www /config/caddy

WORKDIR /app

# persistent / runtime deps
# hadolint ignore=DL3008
RUN apt-get update && apt-get install -y --no-install-recommends \
	acl \
	file \
	gettext \
	git \
    cron \
	&& apt-get clean && rm -rf /var/lib/apt/lists/*

RUN set -eux; \
	install-php-extensions \
		@composer \
		apcu \
		intl \
		opcache \
		zip \
        gd \
        exif \
	;

RUN echo "0 * * * * cd /app && /usr/local/bin/php artisan clear:hourly > /dev/null 2>&1" > /etc/cron.d/clear_hourly && \
        crontab -u www-data /etc/cron.d/clear_hourly \
        && chmod u+s /usr/sbin/cron

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

###> recipes ###
###< recipes ###

COPY --link frankenphp/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link frankenphp/Caddyfile /etc/caddy/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1
CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]

# Dev FrankenPHP image
FROM frankenphp_base AS frankenphp_dev

ENV XDEBUG_MODE=off

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" && \
    install-php-extensions xdebug && \
    curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - && \
    apt-get install -y nodejs && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --link frankenphp/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

USER ${USER}

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch" ]

FROM frankenphp_base AS vendor

COPY --link composer.* ./

RUN set -eux; \
    composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit;

FROM node:20-alpine AS build

WORKDIR /app

RUN npm config set update-notifier false && npm set progress=false

COPY --link package*.json ./

RUN if [ -f $ROOT/package-lock.json ]; \
    then \
    npm ci --loglevel=error --no-audit; \
    else \
    npm install --loglevel=error --no-audit; \
    fi

COPY --link --from=vendor /app/vendor vendor

COPY --link . .

RUN npm run build

# Prod FrankenPHP image
FROM frankenphp_base AS frankenphp_prod

COPY --link frankenphp/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/

COPY --link --from=build --chown=${USER}:${USER} /app/public public

COPY --link --from=vendor --chown=${USER}:${USER} /app/vendor vendor

COPY --link --chown=${USER}:${USER} . .

RUN rm -Rf frankenphp

USER ${USER}

RUN \
    set -eux; \
    mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/framework/testing storage/logs bootstrap/cache && \
    chmod -R a+rw storage && \
    composer dump-autoload --optimize --classmap-authoritative --no-interaction --no-ansi --no-dev && \
    composer clear-cache && \
    php artisan storage:link && sync
