#!/usr/bin/env bash

set -Eeuo pipefail

dockerfile=${1:-Dockerfile}
composer_file=${2:-composer.json}
workflow_files=(
    .github/workflows/build-image.yml
    .github/workflows/ci.yml
)

image_ref=$(awk '
    $1 == "FROM" && $2 ~ /^serversideup\/php:[^@[:space:]]+@sha256:[0-9a-f]{64}$/ {
        references[++count] = $2
    }
    END {
        if (count != 1) {
            printf "Expected exactly one digest-pinned ServerSideUp base, found %d.\n", count > "/dev/stderr"
            exit 1
        }

        print references[1]
    }
' "$dockerfile")

if [[ ! "$image_ref" =~ ^serversideup/php:([0-9]+\.[0-9]+\.[0-9]+)-frankenphp@sha256:[0-9a-f]{64}$ ]]; then
    echo "Expected one digest-pinned serversideup/php:<version>-frankenphp base in $dockerfile." >&2
    exit 1
fi

production_php_version=${BASH_REMATCH[1]}

if ! composer_php_version=$(jq -er '.config.platform.php // empty' "$composer_file"); then
    echo "Expected config.platform.php in $composer_file." >&2
    exit 1
fi

if [[ "$composer_php_version" != "$production_php_version" ]]; then
    echo "Composer targets PHP $composer_php_version, but the production image uses PHP $production_php_version." >&2
    exit 1
fi

declaration_pattern="^[[:space:]]*php-version:[[:space:]]*'([0-9]+\.[0-9]+\.[0-9]+)'[[:space:]]*$"
declaration_count=0

for workflow_file in "${workflow_files[@]}"; do
    while IFS= read -r declaration; do
        ((declaration_count += 1))

        if [[ ! "$declaration" =~ $declaration_pattern ]]; then
            echo "Expected an exact PHP patch version in $workflow_file: $declaration" >&2
            exit 1
        fi

        workflow_php_version=${BASH_REMATCH[1]}

        if [[ "$workflow_php_version" != "$production_php_version" ]]; then
            echo "$workflow_file targets PHP $workflow_php_version, but the production image uses PHP $production_php_version." >&2
            exit 1
        fi
    done < <(awk '/php-version:/' "$workflow_file")
done

if ((declaration_count == 0)); then
    echo "Expected at least one setup-php version declaration." >&2
    exit 1
fi

echo "Verified PHP $production_php_version across Docker, Composer and $declaration_count setup-php declarations."
