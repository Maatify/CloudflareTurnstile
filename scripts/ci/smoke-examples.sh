#!/usr/bin/env bash
# Smoke-executes the standalone examples against the deterministic local
# provider stand-in (tests/Support/router.php). Cloudflare is never contacted.
# validate-form-token.php is Host-dependent (needs a real token and secret) and
# is validated for syntax and static analysis only.
set -euo pipefail

root="$(cd "$(dirname "$0")/../.." && pwd)"
php_bin="$(command -v php)"

socket_port="$("${php_bin}" -r '$s = stream_socket_server("tcp://127.0.0.1:0"); echo (int) substr(stream_socket_get_name($s, false), strrpos(stream_socket_get_name($s, false), ":") + 1);')"
base="http://127.0.0.1:${socket_port}"

"${php_bin}" -S "127.0.0.1:${socket_port}" "${root}/tests/Support/router.php" >/dev/null 2>&1 &
server_pid=$!
trap 'kill "${server_pid}" 2>/dev/null || true' EXIT

for _ in $(seq 1 50); do
    if "${php_bin}" -r '$p = @fsockopen("127.0.0.1", (int) $argv[1], $e, $m, 0.1); exit($p === false ? 1 : 0);' "${socket_port}"; then
        break
    fi
    sleep 0.1
done

expect() {
    local label="$1" needle="$2" output="$3"
    if [[ "${output}" != *"${needle}"* ]]; then
        echo "FAIL ${label}: expected output to contain: ${needle}" >&2
        echo "${output}" >&2
        exit 1
    fi
    echo "ok   ${label}"
}

out="$("${php_bin}" -d display_errors=0 "${root}/examples/validate-with-test-double.php" "${base}/object")"
expect "validate-with-test-double (accepted)" "accepted" "${out}"

out="$("${php_bin}" -d display_errors=0 "${root}/examples/validate-with-test-double.php" "${base}/errors")"
expect "validate-with-test-double (rejected)" "rejected" "${out}"
expect "validate-with-test-double (error code)" "invalid-input-secret" "${out}"

out="$("${php_bin}" -d display_errors=0 "${root}/examples/json-error-response.php" "${base}/object")"
expect "json-error-response (success continues)" "validated" "${out}"

out="$("${php_bin}" -d display_errors=0 "${root}/examples/json-error-response.php" "${base}/errors")"
expect "json-error-response (failure ends with JSON)" "invalid-input-secret" "${out}"
if [[ "${out}" == *"validated"* ]]; then
    echo "FAIL json-error-response: the request must end on a failed validation" >&2
    exit 1
fi
echo "ok   json-error-response (request ended)"
