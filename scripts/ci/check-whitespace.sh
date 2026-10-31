#!/usr/bin/env bash
# Fails on whitespace defects introduced since the comparison base.
# CI_DIFF_BASE: pull_request -> base sha; push -> previous sha; empty/all-zero
# (workflow_dispatch, first push) or equal to HEAD -> fall back to the merge-base
# with origin/main, then to HEAD's parent, then to the empty tree, so HEAD is
# never compared with itself.
set -euo pipefail

head_sha="$(git rev-parse HEAD)"
base="${CI_DIFF_BASE:-}"

if [[ -z "${base}" || "${base}" =~ ^0+$ ]]; then
    base="$(git merge-base HEAD origin/main 2>/dev/null || true)"
fi
if [[ -z "${base}" || "$(git rev-parse "${base}^{commit}" 2>/dev/null || true)" == "${head_sha}" ]]; then
    base="$(git rev-parse --verify -q HEAD~1 || git hash-object -t tree /dev/null)"
fi

echo "Whitespace check: ${base} -> ${head_sha}"
git diff --check "${base}" "${head_sha}"
