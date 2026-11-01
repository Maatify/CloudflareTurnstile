#!/usr/bin/env bash
# Inspects the archive Composer would ship for the committed tree
# (`git archive HEAD`, which honors .gitattributes export-ignore): every
# consumer-facing file must be present and no development file may leak in.
set -euo pipefail

root="$(cd "$(dirname "$0")/../.." && pwd)"
listing="$(git -C "${root}" archive HEAD | tar -t)"

required=(
    composer.json LICENSE README.md CHANGELOG.md SECURITY.md CONTRIBUTING.md CODE_OF_CONDUCT.md
    llms.txt CLOUDFLARE_TURNSTILE_PACKAGE_REFERENCE.md docs/guides/USAGE_GUIDE.md
    src/Adapter/TurnstileRequestCall.php src/Service/TurnstileValidation.php
    examples/validate-form-token.php examples/validate-with-test-double.php examples/json-error-response.php
)
excluded=(.github/ harness/ scripts/ tests/ phpstan.neon phpunit.xml .gitattributes .gitignore)

status=0
for path in "${required[@]}"; do
    if ! grep -qxF "${path}" <<<"${listing}"; then
        echo "Required file missing from the archive: ${path}" >&2
        status=1
    fi
done
for path in "${excluded[@]}"; do
    if grep -qE "^${path//./\\.}" <<<"${listing}"; then
        echo "Development path present in the archive: ${path}" >&2
        status=1
    fi
done
[ "${status}" -eq 0 ] && echo "Archive content check passed."
exit "${status}"
