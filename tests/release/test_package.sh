#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/../../docker-lite/lib/common.sh"
source "$SCRIPT_DIR/../../docker-lite/lib/package.sh"

assert_eq() {
  local name=$1 expected=$2 actual=$3
  if [ "$expected" != "$actual" ]; then
    echo "FAIL $name: expected='$expected' actual='$actual'"
    exit 1
  fi
  echo "PASS $name"
}

assert_eq archive_name "ecshopx-4.12.0" "$(release_archive_basename 4.12.0)"

TMP=$(mktemp -d)
OUT=$(mktemp -d)
trap 'rm -rf "$TMP" "$OUT"' EXIT

mkdir -p "$TMP/ECShopX/vendor" \
  "$TMP/ECShopX/docker-new" \
  "$TMP/ECShopX/docker-lite" \
  "$TMP/ECShopX/docker-dev/demo" \
  "$TMP/ECShopX_admin-frontend/dist-b2c" \
  "$TMP/ECShopX_admin-frontend/node_modules/pkg" \
  "$TMP/ECShopX_mobile-frontend/dist-b2c/h5" \
  "$TMP/ECShopX_web-frontend/.output/server"
touch "$TMP/ECShopX/vendor/autoload.php"
touch "$TMP/ECShopX/docker-new/Dockerfile"
touch "$TMP/ECShopX/docker-lite/Dockerfile"
touch "$TMP/ECShopX/docker-lite/entrypoint.sh"
touch "$TMP/ECShopX/docker-lite/docker-compose.yml"
touch "$TMP/ECShopX/docker-lite/images.env"
touch "$TMP/ECShopX/docker-lite/nginx.conf"
mkdir -p "$TMP/ECShopX/docker-lite/supervisord.d" "$TMP/ECShopX/docker-lite/cron"
touch "$TMP/ECShopX/docker-lite/supervisord.d/app.ini"
touch "$TMP/ECShopX/docker-lite/supervisord.d/super-queue.ini"
touch "$TMP/ECShopX/docker-lite/web-entrypoint.sh"
touch "$TMP/ECShopX/docker-lite/cron/root"
touch "$TMP/ECShopX/docker-compose.dev.yml"
touch "$TMP/ECShopX/docker-dev/Dockerfile"
echo 'INSERT 1;' >"$TMP/ECShopX/docker-dev/demo/bbc.sql"
touch "$TMP/ECShopX_admin-frontend/dist-b2c/index.html"
touch "$TMP/ECShopX_admin-frontend/node_modules/pkg/x.js"
touch "$TMP/ECShopX_web-frontend/.output/server/index.mjs"
mkdir -p "$TMP/ECShopX_web-frontend/.output/server/node_modules/vue-router"
touch "$TMP/ECShopX_web-frontend/.output/server/node_modules/vue-router/package.json"
echo '#!/bin/bash' > "$TMP/ECShopX/deploy.sh"
echo 'APP_KEY=must-not-ship' > "$TMP/ECShopX/.env"
echo 'VUE_APP_SECRET=must-not-ship' > "$TMP/ECShopX_admin-frontend/.env"

RELEASE_PARENT_DIR="$TMP"
RELEASE_ECSHOPX_ROOT="$TMP/ECShopX"
release_build_tarball "4.12.0" "$OUT"

test -f "$OUT/ecshopx-4.12.0.tar.gz"
EXTRACT=$(mktemp -d)
tar -xzf "$OUT/ecshopx-4.12.0.tar.gz" -C "$EXTRACT"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/vendor/autoload.php"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/deploy.sh"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/docker-lite/Dockerfile"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/docker-lite/docker-compose.yml"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/docker-lite/nginx.conf"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/docker-lite/supervisord.d/app.ini"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/docker-lite/supervisord.d/super-queue.ini"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/docker-lite/web-entrypoint.sh"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/docker-new/demo/bbc.sql"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/docker-dev/Dockerfile"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/docker-dev/demo/bbc.sql"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX/docker-compose.dev.yml"
test -f "$EXTRACT/ecshopx-4.12.0/ECShopX_web-frontend/.output/server/node_modules/vue-router/package.json"
if [ -e "$EXTRACT/ecshopx-4.12.0/ECShopX_admin-frontend/node_modules" ]; then
  echo "FAIL top-level node_modules should be excluded"
  exit 1
fi
if [ -e "$EXTRACT/ecshopx-4.12.0/ECShopX/.env" ]; then
  echo "FAIL backend .env should be excluded from tarball"
  exit 1
fi
if [ -e "$EXTRACT/ecshopx-4.12.0/ECShopX_admin-frontend/.env" ]; then
  echo "FAIL admin frontend .env should be excluded from tarball"
  exit 1
fi
echo "PASS package"
