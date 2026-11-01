#!/usr/bin/env bash
# Release Artifact Verification for an exact commit of this repository.
# Builds the distribution archive from <ref> (git archive, honoring
# .gitattributes), checks its content and links, validates the manifest, and
# runs the consumer harness in latest and lowest dependency modes. Prints a
# record (target, commit, tree, PHP, results) to attach to the release review.
#
# This verifies the candidate artifact before publication. After publication the
# real Composer dist must be verified separately.
#
# Usage: scripts/release/verify-artifact.sh <ref>
# Optional local-only knob: HARNESS_COMPOSER_ARGS (never set in CI).
set -euo pipefail

ref="${1:?Usage: scripts/release/verify-artifact.sh <ref>}"
root="$(cd "$(dirname "$0")/../.." && pwd)"

commit="$(git -C "${root}" rev-parse --verify "${ref}^{commit}")"
tree="$(git -C "${root}" rev-parse "${commit}^{tree}")"
target="$(git -C "${root}" show "${commit}:CHANGELOG.md" | sed -n 's/^## \[\([0-9][0-9A-Za-z.+-]*\)\]$/\1/p' | head -n 1)"
if [ -z "${target}" ]; then
    echo "No undated exact-target section found in CHANGELOG.md at ${commit}." >&2
    exit 1
fi

work="$(mktemp -d)"
trap 'rm -rf "${work}"' EXIT
git clone --quiet "${root}" "${work}/repo"
git -C "${work}/repo" checkout --quiet --detach "${commit}"

results=()
run() {
    local label="$1"
    shift
    echo "-- ${label}"
    if (cd "${work}/repo" && "$@"); then
        results+=("PASS ${label}")
    else
        results+=("FAIL ${label}")
    fi
}

run "composer validate --strict" composer validate --strict
run "archive content and links" scripts/ci/check-archive.sh HEAD
run "consumer harness (latest)" harness/run.sh latest
run "consumer harness (lowest)" harness/run.sh lowest

echo
echo "== Release Artifact Verification record"
echo "Target:  ${target}"
echo "Commit:  ${commit}"
echo "Tree:    ${tree}"
echo "PHP:     $(php -r 'echo PHP_VERSION;')"
failed=0
for line in "${results[@]}"; do
    echo "${line}"
    [[ "${line}" == FAIL* ]] && failed=1
done
[ "${failed}" -eq 0 ] && echo "Result:  PASS" || echo "Result:  FAIL"
exit "${failed}"
