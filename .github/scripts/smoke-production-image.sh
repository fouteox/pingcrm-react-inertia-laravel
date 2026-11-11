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
    # Bundle SSR autonome (ssr.noExternal) : aucun node_modules dans cette image.
    test ! -e node_modules
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
    --env BROADCAST_CONNECTION=log
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
    --env REVERB_APP_KEY=smoke
    --env SESSION_DRIVER=array
)

docker run --rm --network host \
    "${runtime_env[@]}" \
    --entrypoint php \
    "$image_ref" \
    artisan migrate --force --no-interaction

containers=()

cleanup() {
    if ((${#containers[@]})); then
        docker rm --force "${containers[@]}" >/dev/null 2>&1 || true
    fi
}

trap cleanup EXIT

# Lance l'image en arrière-plan, avec sa commande par défaut ou celle passée
# en argument, et garde l'identifiant du conteneur dans container_id.
start_container() {
    container_id=$(docker run --detach --network host \
        "${runtime_env[@]}" \
        --env AUTORUN_ENABLED=false \
        --env HEALTHCHECK_PATH=/up \
        --env SSL_MODE=off \
        "$image_ref" "$@")
    containers+=("$container_id")
}

# Serveur SSR lancé comme le pod ssr de gitops. Il tourne sans node_modules :
# s'il ne démarre pas ou ne rend pas la page, le bundle n'est pas autonome.
start_container php artisan inertia:start-ssr --runtime=bun --no-stop-before-start
ssr_container_id=$container_id
ssr_healthy=false

for _ in {1..30}; do
    if [[ $(docker inspect --format '{{.State.Running}}' "$ssr_container_id") != true ]]; then
        break
    fi

    if curl --fail --silent http://127.0.0.1:13714/health >/dev/null; then
        ssr_healthy=true
        break
    fi

    sleep 1
done

if [[ "$ssr_healthy" != true ]]; then
    docker logs "$ssr_container_id" >&2
    echo 'Le serveur SSR n'"'"'a pas répondu sur /health en 30 secondes.' >&2
    exit 1
fi

start_container

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

# Inertia retombe en silence sur le rendu client quand le SSR échoue : la page
# doit donc porter la marque du rendu serveur, suivie du texte attendu. Sans
# Accept-Language, SetLocaleMiddleware retient le français : la langue est donc
# demandée explicitement pour que le texte cherché soit déterministe.
page=$(curl --fail --silent --show-error --header 'Accept-Language: en' http://127.0.0.1:8080/login)
markup=${page#*data-server-rendered=\"true\"}

if [[ "$markup" == "$page" ]]; then
    docker logs "$ssr_container_id" >&2
    docker logs "$container_id" >&2
    echo 'La page /login ne porte pas la marque du rendu SSR (data-server-rendered).' >&2
    exit 1
fi

if [[ "$markup" != *'Remember me'* ]]; then
    docker logs "$ssr_container_id" >&2
    docker logs "$container_id" >&2
    echo 'Le rendu SSR de /login ne contient pas « Remember me ».' >&2
    exit 1
fi
