#!/usr/bin/env bash

set -Eeuo pipefail

if [[ $# -ne 1 ]]; then
    echo "Usage: $0 <image-reference>" >&2
    exit 64
fi

image_ref=$1
expected_architecture=${EXPECTED_ARCHITECTURE:-amd64}
actual_architecture=$(docker image inspect --format '{{.Architecture}}' "$image_ref")

if [[ "$actual_architecture" != "$expected_architecture" ]]; then
    echo "Expected image architecture $expected_architecture, got $actual_architecture." >&2
    exit 1
fi

docker run --rm --entrypoint sh "$image_ref" -euc '
    bun --version
    test -s public/build/manifest.json
    find bootstrap/ssr -maxdepth 1 -type f -name "*.js" -print -quit | grep -q .
    php -r '\''
        foreach (["bcmath", "intl", "pdo_pgsql"] as $extension) {
            if (! extension_loaded($extension)) {
                fwrite(STDERR, "Missing PHP extension: {$extension}\n");
                exit(1);
            }
        }
    '\''
'

runtime_env=(
    --env APP_ENV=production
    --env APP_DEBUG=false
    --env APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
    --env APP_URL=http://127.0.0.1:8080
    --env CACHE_STORE=array
    --env DB_CONNECTION=pgsql
    --env DB_HOST=127.0.0.1
    --env DB_PORT=5432
    --env DB_DATABASE=pingcrm_image_smoke
    --env DB_USERNAME=postgres
    --env DB_PASSWORD=secret
    --env LOG_CHANNEL=stderr
    --env MAIL_MAILER=log
    --env QUEUE_CONNECTION=sync
    --env SESSION_DRIVER=array
)

docker run --rm --network host \
    "${runtime_env[@]}" \
    --entrypoint php \
    "$image_ref" \
    artisan migrate --force --no-interaction

container_id=''

cleanup() {
    if [[ -n "$container_id" ]]; then
        docker rm --force "$container_id" >/dev/null 2>&1 || true
    fi
}

trap cleanup EXIT

container_id=$(docker run --detach --network host \
    "${runtime_env[@]}" \
    --env AUTORUN_ENABLED=false \
    --env HEALTHCHECK_PATH=/up \
    --env SSL_MODE=off \
    "$image_ref")

health=starting

for _ in {1..30}; do
    if [[ $(docker inspect --format '{{.State.Running}}' "$container_id") != true ]]; then
        docker logs "$container_id" >&2
        echo "Production image exited before becoming healthy." >&2
        exit 1
    fi

    health=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}missing{{end}}' "$container_id")

    if [[ "$health" == healthy ]]; then
        break
    fi

    if [[ "$health" == unhealthy || "$health" == missing ]]; then
        docker logs "$container_id" >&2
        echo "Production image health status: $health" >&2
        exit 1
    fi

    sleep 2
done

if [[ "$health" != healthy ]]; then
    docker logs "$container_id" >&2
    echo "Production image did not become healthy within 60 seconds." >&2
    exit 1
fi

curl --fail --silent --show-error http://127.0.0.1:8080/up >/dev/null
