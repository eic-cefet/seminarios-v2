#!/bin/bash
# Sourced by entrypoint.sh, worker.sh, and scheduler.sh BEFORE the service
# boots (and before config:cache on the server). When AWS_ENV_SECRET_ID is
# set, fetches that Secrets Manager secret (a JSON key/value object) and
# exports every entry, overriding any env var that already exists.
# A configured-but-unfetchable secret aborts startup (callers run set -e).

if [ -n "$AWS_ENV_SECRET_ID" ]; then
    echo "Loading environment overrides from AWS secret: $AWS_ENV_SECRET_ID" >&2
    _SECRET_EXPORTS="$(php "$(dirname "${BASH_SOURCE[0]}")/load-env-secrets.php")" || return 1
    eval "$_SECRET_EXPORTS"
    unset _SECRET_EXPORTS
fi
