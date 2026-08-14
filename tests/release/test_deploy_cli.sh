#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DEPLOY="$SCRIPT_DIR/../../docker-lite/deploy.sh"
COMPOSE="$SCRIPT_DIR/../../docker-lite/docker-compose.yml"
test -f "$DEPLOY" || { echo "FAIL deploy.sh missing"; exit 1; }
test -f "$COMPOSE" || { echo "FAIL docker-compose.yml missing"; exit 1; }
bash -n "$DEPLOY"
# Forbidden install/build invocations (app-layer)
if grep -E 'composer[[:space:]]+install|npm[[:space:]]+install|pnpm[[:space:]]+install|npm[[:space:]]+run[[:space:]]+build|pnpm[[:space:]]+build' "$DEPLOY"; then
  echo "FAIL deploy.sh must not install deps or build frontends"
  exit 1
fi
grep -q 'docker-lite/docker-compose.yml' "$DEPLOY" || {
  echo "FAIL deploy.sh must use docker-lite/docker-compose.yml"
  exit 1
}
grep -q 'release_ensure_runtime_images' "$DEPLOY" || {
  echo "FAIL deploy.sh must load runtime image tars"
  exit 1
}
grep -q 'deploy_ensure_bind_mount_traverse' "$DEPLOY" || {
  echo "FAIL deploy.sh must ensure bind-mount traverse perms for www-data"
  exit 1
}
if grep -E 'docker-compose\.dev\.yml|docker-dev/' "$DEPLOY" | grep -vE '^\s*#' >/dev/null; then
  echo "FAIL deploy.sh must not depend on docker-compose.dev.yml or docker-dev/"
  exit 1
fi
grep -Eq '8080:8080|ADMIN_HOST_PORT' "$COMPOSE" || {
  echo "FAIL compose must publish admin 8080"
  exit 1
}
grep -q 'nuxt' "$SCRIPT_DIR/../../docker-lite/supervisord.d/app.ini" || {
  echo "FAIL app.ini must run nuxt"
  exit 1
}
test -f "$SCRIPT_DIR/../../docker-lite/nginx.conf" || {
  echo "FAIL docker-lite/nginx.conf missing"
  exit 1
}
test -f "$SCRIPT_DIR/../../docker-lite/supervisord.d/super-queue.ini" || {
  echo "FAIL super-queue.ini missing"
  exit 1
}
echo "PASS deploy_cli_guards"
