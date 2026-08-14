#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
fail=0
for t in "$ROOT"/test_*.sh; do
  echo "==> $(basename "$t")"
  if ! bash "$t"; then
    fail=1
  fi
done
exit "$fail"
