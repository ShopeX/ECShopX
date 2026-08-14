#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/../../docker-lite/lib/common.sh"

assert_eq() {
  local name=$1 expected=$2 actual=$3
  if [ "$expected" != "$actual" ]; then
    echo "FAIL $name: expected='$expected' actual='$actual'"
    exit 1
  fi
  echo "PASS $name"
}

# Missing version arg should fail
if release_nvm_use >/dev/null 2>&1; then
  echo "FAIL expected release_nvm_use without version to fail"
  exit 1
fi
echo "PASS nvm_use_requires_version"

# Missing nvm.sh should fail clearly
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
export NVM_DIR="$TMP/empty-nvm"
mkdir -p "$NVM_DIR"
if release_load_nvm >/dev/null 2>&1; then
  echo "FAIL expected release_load_nvm to fail without nvm.sh"
  exit 1
fi
echo "PASS nvm_missing_sh"

# If real nvm is available, smoke-switch to 16.20 (already commonly installed for this repo)
REAL_NVM_DIR="${HOME}/.nvm"
if [ -s "$REAL_NVM_DIR/nvm.sh" ]; then
  export NVM_DIR="$REAL_NVM_DIR"
  release_nvm_use 16.20
  case "$(node -v)" in
    v16.20.*)
      echo "PASS nvm_use_16.20"
      ;;
    *)
      echo "FAIL nvm_use_16.20: got $(node -v)"
      exit 1
      ;;
  esac
else
  echo "SKIP nvm_use_16.20 (no real nvm)"
fi

# pack.sh should mention nvm switching and not require global pnpm at startup gate
PACK="$SCRIPT_DIR/../../docker-lite/pack.sh"
grep -q 'release_nvm_use' "$PACK" || { echo "FAIL pack.sh missing release_nvm_use"; exit 1; }
grep -q 'RELEASE_NODE_ADMIN_MOBILE' "$PACK" || { echo "FAIL pack.sh missing RELEASE_NODE_ADMIN_MOBILE"; exit 1; }
grep -q 'release_ensure_pnpm' "$PACK" || { echo "FAIL pack.sh missing release_ensure_pnpm"; exit 1; }
if grep -E 'release_require_cmds[[:space:]]+.*pnpm' "$PACK" >/dev/null; then
  echo "FAIL pack.sh should not require global pnpm before nvm switch"
  exit 1
fi
echo "PASS pack_nvm_wiring"
