#!/usr/bin/env bash
# Consumer Verification Harness (TESTING_STANDARD §3.4), run identically locally
# and in CI (CI_WORKFLOW_STANDARD §2.2). It builds the package artifact from the
# committed tree (`git archive HEAD`), installs it into a clean consumer project
# through Composer, and runs a realistic workflow via the production autoload.
# The whole cycle runs twice, each from a fresh temporary state.
#
# Cloudflare is never contacted. Optional local-only knob: HARNESS_COMPOSER_ARGS
# (never set in CI), e.g. when the local PHP is below the declared patch floor;
# it also skips the platform check, which CI always runs.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck disable=SC2206
composer_args=(${HARNESS_COMPOSER_ARGS:-})
work="$(mktemp -d)"
server_pid=""

cleanup() {
    if [[ -n "${server_pid}" ]]; then
        kill "${server_pid}" 2>/dev/null || true
    fi
    rm -rf "${work}"
}
trap cleanup EXIT

# shellcheck disable=SC2016
port="$(php -r '$s = stream_socket_server("tcp://127.0.0.1:0"); echo substr(stream_socket_get_name($s, false), 10);')"
php -S "127.0.0.1:${port}" "${root}/harness/router.php" >/dev/null 2>&1 &
server_pid=$!
for _ in $(seq 1 50); do
    if (exec 3<>"/dev/tcp/127.0.0.1/${port}") 2>/dev/null; then break; fi
    sleep 0.1
done

for run in 1 2; do
    echo "== Consumer harness run ${run}/2"
    state="${work}/run-${run}"
    package="${state}/package"
    consumer="${state}/consumer"
    mkdir -p "${package}" "${consumer}"

    git -C "${root}" archive HEAD | tar -x -C "${package}"
    cp "${root}/harness/consumer/workflow.php" "${consumer}/workflow.php"
    sed "s|__PACKAGE_DIR__|${package}|" "${root}/harness/consumer/composer.json" > "${consumer}/composer.json"

    (cd "${consumer}" && composer update --no-interaction --prefer-dist --no-progress ${composer_args[@]+"${composer_args[@]}"})
    if [[ -z "${HARNESS_COMPOSER_ARGS:-}" ]]; then
        (cd "${consumer}" && composer check-platform-reqs)
    fi

    output="$(cd "${consumer}" && php workflow.php "http://127.0.0.1:${port}")"
    echo "${output}"

    # shellcheck disable=SC2016
    php -r '
        $r = json_decode($argv[1], true, flags: JSON_THROW_ON_ERROR);
        $ok = $r["loaded_from_vendor"] === true
            && $r["results"]["valid-token"]["success"] === true
            && $r["results"]["valid-token"]["response"]["hostname"] === "example.com"
            && $r["results"]["invalid-token"]["success"] === false
            && $r["results"]["invalid-token"]["response"]["error-codes"] === ["invalid-input-response"];
        if (!$ok) { fwrite(STDERR, "Harness workflow result did not match the expected contract.\n"); exit(1); }
    ' "${output}"
done

echo "OK: consumer harness passed twice from clean states."
