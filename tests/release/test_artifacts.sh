#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/../../docker-lite/lib/common.sh"
source "$SCRIPT_DIR/../../docker-lite/lib/artifacts.sh"

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

# Fake four-project layout
mkdir -p "$TMP/ECShopX/vendor"
touch "$TMP/ECShopX/vendor/autoload.php"
mkdir -p "$TMP/ECShopX_admin-frontend/dist-b2c" "$TMP/ECShopX_admin-frontend/dist-bbc"
echo ok > "$TMP/ECShopX_admin-frontend/dist-b2c/index.html"
echo ok > "$TMP/ECShopX_admin-frontend/dist-bbc/index.html"
mkdir -p "$TMP/ECShopX_mobile-frontend/dist-b2c/h5" "$TMP/ECShopX_mobile-frontend/dist-bbc/h5"
echo ok > "$TMP/ECShopX_mobile-frontend/dist-b2c/h5/index.html"
echo ok > "$TMP/ECShopX_mobile-frontend/dist-bbc/h5/index.html"
mkdir -p "$TMP/ECShopX_web-frontend/.output/server"
echo ok > "$TMP/ECShopX_web-frontend/.output/server/index.mjs"

RELEASE_ECSHOPX_ROOT="$TMP/ECShopX"
RELEASE_PARENT_DIR="$TMP"
RELEASE_ADMIN_DIR="$TMP/ECShopX_admin-frontend"
RELEASE_MOBILE_DIR="$TMP/ECShopX_mobile-frontend"
RELEASE_PC_DIR="$TMP/ECShopX_web-frontend"

release_validate_prebuilt_artifacts
release_activate_frontend_dist b2c

test -f "$RELEASE_ADMIN_DIR/dist/index.html"
test -f "$RELEASE_MOBILE_DIR/dist/h5/index.html"
grep -q ok "$RELEASE_ADMIN_DIR/dist/index.html"

# Missing vendor must fail
rm -f "$TMP/ECShopX/vendor/autoload.php"
if release_validate_prebuilt_artifacts >/dev/null 2>&1; then
  echo "FAIL expected validate to fail without vendor"
  exit 1
fi
echo "PASS artifacts"
