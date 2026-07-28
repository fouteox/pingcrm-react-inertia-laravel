#!/usr/bin/env bash

set -Eeuo pipefail

dockerfile=${1:-Dockerfile}
image_ref=${IMAGE_REF:-}

if [[ -z "$image_ref" ]]; then
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
fi

if [[ ! "$image_ref" =~ ^serversideup/php:([0-9]+\.[0-9]+\.[0-9]+)-frankenphp@(sha256:[0-9a-f]{64})$ ]]; then
    echo "Expected one digest-pinned serversideup/php:<version>-frankenphp base in $dockerfile." >&2
    exit 1
fi

php_version=${BASH_REMATCH[1]}
declared_index_digest=${BASH_REMATCH[2]}
repository=serversideup/php
work_dir=$(mktemp -d)

cleanup() {
    rm -rf "$work_dir"
}

trap cleanup EXIT

index_file="$work_dir/index.json"
docker buildx imagetools inspect "$image_ref" --raw > "$index_file"

actual_index_digest="sha256:$(sha256sum "$index_file" | awk '{ print $1 }')"

if [[ "$actual_index_digest" != "$declared_index_digest" ]]; then
    echo "Index digest mismatch: expected $declared_index_digest, got $actual_index_digest." >&2
    exit 1
fi

jq -e '
    .mediaType == "application/vnd.oci.image.index.v1+json"
    and ([.manifests[] | select(.platform.os == "linux" and (.platform.architecture == "amd64" or .platform.architecture == "arm64"))] | length == 2)
' "$index_file" >/dev/null

registry_token=$(curl \
    --fail \
    --silent \
    --show-error \
    --location \
    --retry 3 \
    --retry-all-errors \
    --get \
    --data-urlencode service=registry.docker.io \
    --data-urlencode "scope=repository:$repository:pull" \
    https://auth.docker.io/token | jq -er .token)

for architecture in amd64 arm64; do
    platform_digest=$(jq -er \
        --arg architecture "$architecture" \
        '.manifests[]
            | select(.platform.os == "linux" and .platform.architecture == $architecture)
            | .digest' \
        "$index_file")

    attestation_digest=$(jq -er \
        --arg platform_digest "$platform_digest" \
        '.manifests[]
            | select(
                .annotations["vnd.docker.reference.type"] == "attestation-manifest"
                and .annotations["vnd.docker.reference.digest"] == $platform_digest
            )
            | .digest' \
        "$index_file")

    attestation_file="$work_dir/attestation-$architecture.json"
    docker buildx imagetools inspect "$repository@$attestation_digest" --raw > "$attestation_file"

    actual_attestation_digest="sha256:$(sha256sum "$attestation_file" | awk '{ print $1 }')"

    if [[ "$actual_attestation_digest" != "$attestation_digest" ]]; then
        echo "Attestation manifest digest mismatch for linux/$architecture." >&2
        exit 1
    fi

    provenance_digest=$(jq -er '
        .layers[]
        | select(
            .mediaType == "application/vnd.in-toto+json"
            and .annotations["in-toto.io/predicate-type"] == "https://slsa.dev/provenance/v0.2"
        )
        | .digest
    ' "$attestation_file")

    provenance_file="$work_dir/provenance-$architecture.json"
    curl \
        --fail \
        --silent \
        --show-error \
        --location \
        --retry 3 \
        --retry-all-errors \
        --header "Authorization: Bearer $registry_token" \
        "https://registry-1.docker.io/v2/$repository/blobs/$provenance_digest" \
        --output "$provenance_file"

    actual_provenance_digest="sha256:$(sha256sum "$provenance_file" | awk '{ print $1 }')"

    if [[ "$actual_provenance_digest" != "$provenance_digest" ]]; then
        echo "Provenance blob digest mismatch for linux/$architecture." >&2
        exit 1
    fi

    platform_digest_hex=${platform_digest#sha256:}

    jq -e \
        --arg architecture "$architecture" \
        --arg php_version "$php_version" \
        --arg platform_digest "$platform_digest_hex" \
        '
        ._type == "https://in-toto.io/Statement/v0.1"
        and .predicateType == "https://slsa.dev/provenance/v0.2"
        and any(.subject[]?; .digest.sha256 == $platform_digest)
        and (.predicate.builder.id | test("^https://github\\.com/serversideup/docker-php/actions/runs/[0-9]+$"))
        and .predicate.buildType == "https://mobyproject.org/buildkit@v1"
        and (.predicate.invocation.configSource.uri | test("^https://github\\.com/serversideup/docker-php\\.git#[0-9a-f]{40}$"))
        and (.predicate.invocation.configSource.digest.sha1 | test("^[0-9a-f]{40}$"))
        and .predicate.invocation.configSource.entryPoint == "src/variations/frankenphp/Dockerfile"
        and .predicate.invocation.parameters.args["build-arg:PHP_VERSION"] == $php_version
        and .predicate.invocation.parameters.args["build-arg:PHP_VARIATION"] == "frankenphp"
        and .predicate.invocation.environment.platform == ("linux/" + $architecture)
        and .predicate.metadata.completeness.parameters == true
        and .predicate.metadata.completeness.environment == true
        and .predicate.metadata.completeness.materials == true
        and (.predicate.materials | length > 0)
        ' \
        "$provenance_file" >/dev/null

    builder_id=$(jq -er .predicate.builder.id "$provenance_file")
    run_id=${builder_id##*/}
    source_uri=$(jq -er .predicate.invocation.configSource.uri "$provenance_file")
    source_sha=${source_uri##*#}
    declared_source_sha=$(jq -er .predicate.invocation.configSource.digest.sha1 "$provenance_file")

    if [[ "$source_sha" != "$declared_source_sha" ]]; then
        echo "Source commit mismatch in linux/$architecture provenance." >&2
        exit 1
    fi

    run_file="$work_dir/run-$architecture.json"
    gh api "repos/serversideup/docker-php/actions/runs/$run_id" > "$run_file"

    jq -e \
        --arg source_sha "$source_sha" \
        '
        .status == "completed"
        and .conclusion == "success"
        and .head_sha == $source_sha
        and .repository.full_name == "serversideup/docker-php"
        and .head_repository.full_name == "serversideup/docker-php"
        and .path == ".github/workflows/action_publish-images-production.yml"
        and (.event == "schedule" or .event == "release" or .event == "workflow_dispatch")
        ' \
        "$run_file" >/dev/null

    echo "Verified linux/$architecture provenance against ServerSideUp run $run_id."
done

echo "Verified pinned ServerSideUp index $declared_index_digest."
