#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
# shellcheck source=../../docker-lite/lib/common.sh
source "$SCRIPT_DIR/../../docker-lite/lib/common.sh"

assert_eq() {
  local name=$1 expected=$2 actual=$3
  if [ "$expected" != "$actual" ]; then
    echo "FAIL $name: expected='$expected' actual='$actual'"
    exit 1
  fi
  echo "PASS $name"
}

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
printf '%s\n' '{"name":"x","version":"4.12.0"}' > "$TMP/composer.json"
assert_eq version "4.12.0" "$(release_read_product_version "$TMP/composer.json")"
assert_eq mode_b2c "standard" "$(release_mode_to_product_model b2c)"
assert_eq mode_bbc "platform" "$(release_mode_to_product_model bbc)"
assert_eq suffix_b2c "b2c" "$(release_mode_to_dist_suffix b2c)"
assert_eq suffix_bbc "bbc" "$(release_mode_to_dist_suffix bbc)"

if release_mode_to_product_model weird >/dev/null 2>&1; then
  echo "FAIL expected invalid mode to fail"
  exit 1
fi
echo "PASS invalid_mode"

# Fake ECShopX/docker-lite/pack.sh layout under TMP
mkdir -p "$TMP/ECShopX/docker-lite"
touch "$TMP/ECShopX/docker-lite/pack.sh"
release_init_paths "$TMP/ECShopX/docker-lite/pack.sh"
assert_eq lite_dir "$TMP/ECShopX/docker-lite" "$RELEASE_LITE_DIR"
assert_eq ecx_root "$TMP/ECShopX" "$RELEASE_ECSHOPX_ROOT"
assert_eq parent_dir "$TMP" "$RELEASE_PARENT_DIR"
