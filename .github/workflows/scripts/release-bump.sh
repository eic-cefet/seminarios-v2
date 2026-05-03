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

# gather_release_authors <repo> <pr_numbers>
#   <repo>        is "owner/name" (e.g., "eic-cefet/seminarios-v2").
#   <pr_numbers>  is a space-separated string of PR numbers.
# For each PR, fetches the commits and emits TSV "login<TAB>name<TAB>email"
# for the unique commit authors across all PRs. Bot logins (suffix "[bot]")
# and empty logins (orphaned/deleted accounts) are skipped. Order: first
# appearance wins. Network-free callers can override `gh` with a shell
# function for testing.
gather_release_authors() {
    local repo="$1"
    local prs="$2"
    local n
    {
        for n in $prs; do
            # `--jq` renders one TSV line per commit; --paginate covers >100
            # commits per PR. `|| true` swallows transient API errors so a
            # single bad PR fetch doesn't abort the whole release.
            gh api "repos/${repo}/pulls/${n}/commits" \
                --paginate \
                --jq '.[] | "\(.author.login // "")\t\(.commit.author.name)\t\(.commit.author.email)"' \
                2>/dev/null || true
        done
    } | awk -F'\t' '
        $1 == "" { next }                # skip orphaned commits
        $1 ~ /\[bot\]$/ { next }          # skip bot accounts
        !seen[$1]++ { print }             # dedupe by login, preserve order
    '
}

# format_coauthor_trailers
# Reads TSV "login<TAB>name<TAB>email" lines on stdin. Emits one
# "Co-authored-by: <name> <<email>>" line per row. Empty stdin → empty stdout.
format_coauthor_trailers() {
    awk -F'\t' 'NF >= 3 { printf "Co-authored-by: %s <%s>\n", $2, $3 }'
}

# format_assignee_list
# Reads TSV "login<TAB>name<TAB>email" lines on stdin and prints a single
# comma-separated string of logins, capped at 10 entries (GitHub's hard
# limit on PR assignees). Empty stdin → empty stdout, no trailing newline.
format_assignee_list() {
    awk -F'\t' '
        NF >= 1 && $1 != "" {
            count++
            if (count > 10) next
            if (out == "") { out = $1 } else { out = out "," $1 }
        }
        END { if (out != "") print out }
    '
}
