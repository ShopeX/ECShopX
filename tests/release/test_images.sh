#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/../../docker-lite/lib/common.sh"
source "$SCRIPT_DIR/../../docker-lite/lib/images.sh"

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
RELEASE_ECSHOPX_ROOT="$TMP"
RELEASE_LITE_DIR="$TMP/docker-lite"
mkdir -p "$TMP/docker-lite/images"

assert_eq() {
  local name=$1 expected=$2 actual=$3
  if [ "$expected" != "$actual" ]; then
    echo "FAIL $name: expected='$expected' actual='$actual'"
    exit 1
  fi
  echo "PASS $name"
}

assert_eq basename "mysql-8.tar" "$(release_image_tar_basename 'https://cdn.example.com/path/mysql-8.tar')"
assert_eq basename_query "redis.tar" "$(release_image_tar_basename 'https://cdn.example.com/redis.tar?token=1')"

cat >"$TMP/docker-lite/images.env" <<'EOF'
APP_BASE_IMAGE=ecshopx-runtime:test
MYSQL_IMAGE=ecshopx-mysql:test
REDIS_IMAGE=ecshopx-redis:test
EOF
release_load_images_env
assert_eq loaded_mysql "ecshopx-mysql:test" "$MYSQL_IMAGE"

# Fake docker: miss on inspect until pull succeeds (no tar configured)
FAKE_BIN="$TMP/bin"
mkdir -p "$FAKE_BIN"
cat >"$FAKE_BIN/docker" <<'EOF'
#!/usr/bin/env bash
STATE_DIR="${FAKE_DOCKER_STATE:?}"
mkdir -p "$STATE_DIR"
if [ "${1:-}" = "image" ] && [ "${2:-}" = "inspect" ]; then
  ref=$(printf '%s' "${3:-}" | tr '/:' '__')
  [ -f "$STATE_DIR/$ref" ] && exit 0
  exit 1
fi
if [ "${1:-}" = "pull" ]; then
  ref=$(printf '%s' "${2:-}" | tr '/:' '__')
  touch "$STATE_DIR/$ref"
  echo "pulled $2"
  exit 0
fi
echo "unexpected docker $*" >&2
exit 1
EOF
chmod +x "$FAKE_BIN/docker"
FAKE_DOCKER_STATE="$TMP/docker-state" PATH="$FAKE_BIN:$PATH" \
  release_ensure_image "registry.example.com/app:tag" "" || {
  echo "FAIL ensure_image should docker pull when no tar URL"
  exit 1
}
echo "PASS ensure_image_pull_fallback"

echo "PASS images_helpers"
