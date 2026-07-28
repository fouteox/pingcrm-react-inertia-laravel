#!/usr/bin/env bash

set -Eeuo pipefail

extract_image_ref() {
    awk '
        $1 == "FROM" && $2 ~ /^serversideup\/php:[^@[:space:]]+@sha256:[0-9a-f]{64}$/ {
            print $2
            exit
        }
    ' "$1"
}

current_ref=${CURRENT_IMAGE_REF:-}
previous_ref=${PREVIOUS_IMAGE_REF:-}
work_dir=$(mktemp -d)
cache_dir=${TRIVY_CACHE_DIR:-"$work_dir/trivy-cache"}

cleanup() {
    rm -rf "$work_dir"
}

trap cleanup EXIT

if [[ -z "$current_ref" ]]; then
    current_ref=$(extract_image_ref Dockerfile)
fi

if [[ -z "$previous_ref" ]]; then
    : "${BASE_SHA:?BASE_SHA is required when PREVIOUS_IMAGE_REF is not set.}"
    : "${GITHUB_REPOSITORY:?GITHUB_REPOSITORY is required when PREVIOUS_IMAGE_REF is not set.}"

    previous_dockerfile="$work_dir/previous.Dockerfile"
    gh api "repos/$GITHUB_REPOSITORY/contents/Dockerfile?ref=$BASE_SHA" \
        --jq .content | base64 --decode > "$previous_dockerfile"
    previous_ref=$(extract_image_ref "$previous_dockerfile")
fi

if [[ -z "$current_ref" || -z "$previous_ref" ]]; then
    echo "Could not resolve both current and previous ServerSideUp image references." >&2
    exit 1
fi

if [[ "$current_ref" == "$previous_ref" ]]; then
    echo "ServerSideUp base image is unchanged; vulnerability delta scan skipped."
    exit 0
fi

for architecture in amd64 arm64; do
    previous_report="$work_dir/previous-$architecture.json"
    current_report="$work_dir/current-$architecture.json"
    introduced_report="$work_dir/introduced-$architecture.json"

    trivy image \
        --cache-dir "$cache_dir" \
        --quiet \
        --scanners vuln \
        --severity HIGH,CRITICAL \
        --ignore-unfixed \
        --format json \
        --output "$previous_report" \
        --platform "linux/$architecture" \
        "$previous_ref"

    trivy image \
        --cache-dir "$cache_dir" \
        --quiet \
        --scanners vuln \
        --severity HIGH,CRITICAL \
        --ignore-unfixed \
        --format json \
        --output "$current_report" \
        --platform "linux/$architecture" \
        "$current_ref"

    jq -n \
        --slurpfile previous "$previous_report" \
        --slurpfile current "$current_report" \
        '
        def vulnerabilities($report):
            [
                $report.Results[]?.Vulnerabilities[]?
                | {
                    id: .VulnerabilityID,
                    package: .PkgName,
                    severity: .Severity,
                    installed: .InstalledVersion,
                    fixed: .FixedVersion
                }
            ]
            | unique_by(.id, .package);

        vulnerabilities($previous[0]) as $previous_vulnerabilities
        | vulnerabilities($current[0])
        | map(
            . as $candidate
            | select(
                (
                    $previous_vulnerabilities
                    | any(.id == $candidate.id and .package == $candidate.package)
                )
                | not
            )
        )
        ' > "$introduced_report"

    if ! jq -e 'length == 0' "$introduced_report" >/dev/null; then
        echo "::error title=New fixable HIGH/CRITICAL vulnerabilities::linux/$architecture introduces vulnerabilities absent from the previous digest."
        jq -r '.[] | "\(.severity) \(.id) \(.package) \(.installed) -> \(.fixed)"' "$introduced_report"
        exit 1
    fi

    echo "No newly introduced fixable HIGH/CRITICAL vulnerability on linux/$architecture."
done
