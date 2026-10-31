#!/usr/bin/env bash
# Repository-owned quality gate, run identically locally and in CI
# (CI_WORKFLOW_STANDARD §2.1). Usage: scripts/ci/verify.sh [latest|lowest]
# Optional: CI_DIFF_BASE=<commit> sets the whitespace comparison base.
set -euo pipefail

mode="${1:-latest}"
cd "$(dirname "$0")/../.."

case "$mode" in
    latest) update_flags=() ;;
    lowest) update_flags=(--prefer-lowest --prefer-stable) ;;
    *) echo "Unknown dependency mode: $mode" >&2; exit 2 ;;
esac

composer --version
composer validate --strict

composer update ${update_flags[@]+"${update_flags[@]}"} --no-interaction --prefer-dist --no-progress
composer check-platform-reqs

php scripts/ci/check-php-floor.php
php scripts/ci/check-php-constraint.php

find turnstile tests scripts -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null

# Whitespace defects introduced since the comparison base (CI sets CI_DIFF_BASE;
# locally it defaults to the merge-base with origin/main).
diff_base="${CI_DIFF_BASE:-$(git merge-base HEAD origin/main)}"
git diff --check "${diff_base}" HEAD

vendor/bin/phpunit tests

composer audit --no-interaction --abandoned=fail
