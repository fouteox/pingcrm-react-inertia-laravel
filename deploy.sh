#!/bin/sh

set -e

# Lancer Docker Compose
echo "Starting Docker Compose..."
docker compose -f compose.yaml -f compose.prod.yaml up -d --build --wait

# Obtenir le nom du conteneur
CONTAINER_NAME=$(docker ps --filter "name=pingcrm" --format "{{.ID}}")

if [ -z "$CONTAINER_NAME" ]; then
    echo "Error: No container found for prefix pingcrm"
    exit 1
fi

# Vérifier l'existence du fichier .env dans le conteneur
if docker exec -it "$CONTAINER_NAME" test -f /app/.env; then
    echo ".env file already exists. Exiting script."
    exit 0
fi

# Générer APP_KEY
echo "Generating APP_KEY..."
RAW_KEY=$(docker exec -it "$CONTAINER_NAME" php artisan key:generate --show)
APP_KEY=$(echo "$RAW_KEY" | sed -e 's/\x1b\[[0-9;]*m//g' | tr -d '\r')

# Copier .env.prod vers .env et mettre à jour APP_KEY localement
echo "Updating .env file with APP_KEY..."
cp .env.prod .env
sed "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env > .env.tmp && mv .env.tmp .env

# Arrêter le conteneur
echo "Stopping the container..."
docker compose -f compose.yaml -f compose.prod.yaml down --remove-orphans

# Redémarrer Docker Compose avec le nouveau fichier .env
echo "Restarting Docker Compose..."
docker compose -f compose.yaml -f compose.prod.yaml up -d --build --wait

echo "Deployment completed."
