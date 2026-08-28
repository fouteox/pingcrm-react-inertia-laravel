############################################
# Base Stage
############################################
# Digest-pinned (supply chain): reproducible builds, and every upstream
# rebuild of the tag lands as a reviewable Renovate PR (tag + digest kept
# in sync). Renovate moves this base, the exact CI parity container and
# Composer's constraints/lock together; setup-php tracks the same minor line.
FROM serversideup/php:8.5.9-frankenphp@sha256:c8e9d95cd6b83180662f63de646937f3b304041ac4edfbd95ff8bd684467d035 AS base

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

# Install native vite+ (vp) — dev only. VP_NODE_MANAGER=yes makes the install
# non-interactive; vp manages its own Node runtime and the pinned bun under VP_HOME.
ENV VP_HOME=/opt/vite-plus
ENV PATH="/opt/vite-plus/bin:${PATH}"
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl \
    && rm -rf /var/lib/apt/lists/* \
    && curl -fsSL https://vite.plus | VP_NODE_MANAGER=yes bash

# Set www-data UID/GID and let it own VP_HOME so vp can write its runtime there.
RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID  && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID && \
    chown -R www-data:www-data /opt/vite-plus

# Drop privileges back to www-data
USER www-data

############################################
# Builder Stage (vendor only — JS assets are pre-built on the CI runner
# against a real MariaDB so Wayfinder generates types that match production)
############################################
FROM base AS builder

COPY --link composer.json composer.lock ./

RUN test "$(php -r 'echo PHP_VERSION;')" = "$(composer config platform.php)" \
    && composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --audit \
    && composer check-platform-reqs --lock --no-dev

COPY --link . .

RUN composer dump-autoload --classmap-authoritative --no-dev

############################################
# Bun Stage (alias only — the binary is copied into the app image below).
# Declared as a FROM so Dependabot sees and bumps it: images referenced only
# in a COPY --from are invisible to its docker ecosystem.
############################################
FROM oven/bun:1.4-debian@sha256:5bb0f9be3a1a36a03e27c9a9dd894a3b1ad26657155c7df4dda771e17bf872ef AS bun

############################################
# App Image (also runs SSR via `php artisan inertia:start-ssr --runtime=bun`)
############################################
FROM base AS app

COPY --from=bun /usr/local/bin/bun /usr/local/bin/bun

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

# The serversideup base grants cap_net_bind_service to frankenphp (bind <1024).
# Octane listens on 8000, and the file capability makes execve fail with
# "Operation not permitted" under no_new_privs (PSS restricted,
# allowPrivilegeEscalation: false). A plain cp does not preserve xattrs,
# which strips the capability without needing libcap2-bin.
RUN cp /usr/local/bin/frankenphp /tmp/frankenphp \
    && mv /tmp/frankenphp /usr/local/bin/frankenphp

USER www-data
