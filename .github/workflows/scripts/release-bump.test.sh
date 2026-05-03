#!/usr/bin/env bash
# Bash unit tests for release-bump.sh helpers.
# Run with: bash .github/workflows/scripts/release-bump.test.sh
# Stubs the `gh` CLI by defining a shell function — no network required.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
# shellcheck source=./release-bump.sh
source "$SCRIPT_DIR/release-bump.sh"

PASS=0
FAIL=0
FAILURES=()

assert_eq() {
    local name="$1" expected="$2" actual="$3"
    if [ "$expected" = "$actual" ]; then
        PASS=$((PASS + 1))
        echo "  ok - $name"
    else
        FAIL=$((FAIL + 1))
        FAILURES+=("$name")
        echo "  FAIL - $name"
        echo "    expected: $(printf '%q' "$expected")"
        echo "    actual:   $(printf '%q' "$actual")"
    fi
}

# -----------------------------------------------------------------------------
# gh stub
#
# `gather_release_authors` calls:
#   gh api "repos/$REPO/pulls/$N/commits" --paginate --jq '<expr>'
#
# The stub keys off the URL fragment ("pulls/$N/commits") and emits the
# post-jq TSV the helper expects to read on stdout.
# -----------------------------------------------------------------------------
gh() {
    if [ "${1:-}" != "api" ]; then
        echo "gh stub: unexpected subcommand '$1'" >&2
        return 1
    fi
    shift
    local url=""
    while [ $# -gt 0 ]; do
        case "$1" in
            --paginate) shift ;;
            --jq) shift 2 ;;
            *) url="$1"; shift ;;
        esac
    done
    case "$url" in
        repos/owner/repo/pulls/100/commits)
            printf 'alice\tAlice Example\talice@example.com\n'
            printf 'alice\tAlice Example\talice@example.com\n'
            printf 'bob\tBob Example\tbob@example.com\n'
            ;;
        repos/owner/repo/pulls/101/commits)
            printf 'alice\tAlice Example\talice@example.com\n'
            printf 'dependabot[bot]\tdependabot[bot]\t49699333+dependabot[bot]@users.noreply.github.com\n'
            printf 'carol\tCarol Q\tcarol@example.com\n'
            ;;
        repos/owner/repo/pulls/102/commits)
            # PR with an author whose GitHub account was deleted: empty login.
            printf '\tGhost User\tghost@example.com\n'
            ;;
        *)
            echo "gh stub: no fixture for $url" >&2
            return 1
            ;;
    esac
}
export -f gh

# -----------------------------------------------------------------------------
# Tests
# -----------------------------------------------------------------------------

test_gather_release_authors_dedupes_and_strips_bots() {
    local out
    out=$(gather_release_authors "owner/repo" "100 101 102")
    local expected="alice	Alice Example	alice@example.com
bob	Bob Example	bob@example.com
carol	Carol Q	carol@example.com"
    assert_eq "gather_release_authors dedupes by login and skips bots/empty logins" "$expected" "$out"
}

test_gather_release_authors_dedupes_and_strips_bots

# -----------------------------------------------------------------------------
echo
echo "Passed: $PASS, Failed: $FAIL"
if [ "$FAIL" -gt 0 ]; then
    printf 'Failures:\n'
    printf '  - %s\n' "${FAILURES[@]}"
    exit 1
fi
