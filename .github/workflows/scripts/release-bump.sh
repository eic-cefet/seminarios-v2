#!/usr/bin/env bash
# Pure-bash helpers used by .github/workflows/release.yml.
# Sourced (not executed) so functions are available in the workflow shell.

set -euo pipefail

# bump_version <current> <bump>
#   <current> is "vMAJOR.MINOR.PATCH" (the leading "v" is required).
#   <bump>    is one of: patch | minor | major
# Echoes the next version (with the leading "v").
bump_version() {
    local current="$1"
    local bump="$2"
    local version="${current#v}"
    local major minor patch
    IFS='.' read -r major minor patch <<<"$version"

    case "$bump" in
        major) major=$((major + 1)); minor=0; patch=0 ;;
        minor) minor=$((minor + 1)); patch=0 ;;
        patch) patch=$((patch + 1)) ;;
        *) echo "bump_version: unknown bump '$bump'" >&2; return 1 ;;
    esac

    echo "v${major}.${minor}.${patch}"
}

# cumulative_bump <bump1> [bump2] ...
#   Each arg is one of: none | patch | minor | major (case-insensitive).
#   Echoes the highest precedence value across all args (none < patch < minor < major).
#   With zero args, echoes "none".
cumulative_bump() {
    local highest="none"
    local rank=0
    local arg lower current_rank

    for arg in "$@"; do
        lower="$(echo "$arg" | tr '[:upper:]' '[:lower:]')"
        case "$lower" in
            major) current_rank=3 ;;
            minor) current_rank=2 ;;
            patch) current_rank=1 ;;
            none|"") current_rank=0 ;;
            *) continue ;;
        esac
        if [ "$current_rank" -gt "$rank" ]; then
            rank=$current_rank
            highest=$lower
        fi
    done

    echo "$highest"
}
