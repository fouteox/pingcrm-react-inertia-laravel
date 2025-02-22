#!/bin/bash
#ddev-generated
set -e

ENV_FILE="${DDEV_APPROOT}/.ddev/.env.web"

if [ ! -f "$ENV_FILE" ]; then
  echo "Le fichier ${ENV_FILE} n'existe pas."
  exit 1
fi

DATABASE=$(grep '^DATABASE=' "$ENV_FILE" | cut -d '=' -f2- | tr -d '"')

if [ -z "${DATABASE}" ]; then
  echo "La variable DATABASE n'est pas définie dans ${ENV_FILE}. Aucun fichier ne sera supprimé."
  exit 0
fi

COMPOSE_FILES=(
  "docker-compose.depends_on.yaml"
  "docker-compose.healthcheck-mariadb.yaml"
  "docker-compose.healthcheck-mysql.yaml"
  "docker-compose.healthcheck-postgres.yaml"
)

case "${DATABASE}" in
  sqlite)
    for file in "${COMPOSE_FILES[@]}"; do
      rm -f "${DDEV_APPROOT}/.ddev/${file}" && echo " · Supprimé : ${file}"
    done
    ;;
  mariadb)
    rm -f "${DDEV_APPROOT}/.ddev/docker-compose.healthcheck-mysql.yaml" && echo " · Supprimé : docker-compose.healthcheck-mysql.yaml"
    rm -f "${DDEV_APPROOT}/.ddev/docker-compose.healthcheck-postgres.yaml" && echo " · Supprimé : docker-compose.healthcheck-postgres.yaml"
    ;;
  mysql)
    rm -f "${DDEV_APPROOT}/.ddev/docker-compose.healthcheck-mariadb.yaml" && echo " · Supprimé : docker-compose.healthcheck-mariadb.yaml"
    rm -f "${DDEV_APPROOT}/.ddev/docker-compose.healthcheck-postgres.yaml" && echo " · Supprimé : docker-compose.healthcheck-postgres.yaml"
    ;;
  postgres)
    rm -f "${DDEV_APPROOT}/.ddev/docker-compose.healthcheck-mariadb.yaml" && echo " · Supprimé : docker-compose.healthcheck-mariadb.yaml"
    rm -f "${DDEV_APPROOT}/.ddev/docker-compose.healthcheck-mysql.yaml" && echo " · Supprimé : docker-compose.healthcheck-mysql.yaml"
    ;;
  *)
    echo "La valeur de la variable DATABASE est inconnue : ${DATABASE}"
    exit 1
    ;;
esac
