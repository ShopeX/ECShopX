#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PACK="$SCRIPT_DIR/../../docker-lite/pack.sh"
test -f "$PACK" || { echo "FAIL docker-lite/pack.sh missing"; exit 1; }
if grep -E '(^|[^[:alnum:]_-])(docker[[:space:]]+(compose|build|run|pull|load)|docker-compose[[:space:]]+)' "$PACK" >/dev/null; then
  echo "FAIL pack.sh must not invoke docker runtime commands"
  exit 1
fi
bash -n "$PACK"
grep -q 'docker-lite' "$PACK" || { echo "FAIL pack.sh should reference docker-lite"; exit 1; }
echo "PASS pack_cli_syntax"
