#!/usr/bin/env bash
# Shared helpers for pack.sh / deploy.sh (sourced, not executed)

release_log_info() { printf '[INFO] %s\n' "$*"; }
release_log_success() { printf '[SUCCESS] %s\n' "$*"; }
release_log_warning() { printf '[WARNING] %s\n' "$*"; }
release_log_error() { printf '[ERROR] %s\n' "$*" >&2; }

release_init_paths() {
  local caller=$1
  RELEASE_LITE_DIR="$(cd "$(dirname "$caller")" && pwd)"
  RELEASE_CALLER_DIR="$RELEASE_LITE_DIR"
  RELEASE_ECSHOPX_ROOT="$(cd "$RELEASE_LITE_DIR/.." && pwd)"
  RELEASE_PARENT_DIR="$(cd "$RELEASE_ECSHOPX_ROOT/.." && pwd)"
  RELEASE_ADMIN_DIR="$RELEASE_PARENT_DIR/ECShopX_admin-frontend"
  RELEASE_MOBILE_DIR="$RELEASE_PARENT_DIR/ECShopX_mobile-frontend"
  RELEASE_PC_DIR="$RELEASE_PARENT_DIR/ECShopX_web-frontend"
}

release_read_product_version() {
  local composer_json=$1
  if [ ! -f "$composer_json" ]; then
    release_log_error "composer.json not found: $composer_json"
    return 1
  fi
  # Prefer PHP for reliable JSON; fallback to sed for minimal envs in unit tests
  if command -v php >/dev/null 2>&1; then
    php -r '$j=json_decode(file_get_contents($argv[1]), true); if (!isset($j["version"])) exit(1); echo $j["version"];' "$composer_json"
    return
  fi
  local ver
  ver=$(sed -n 's/.*"version"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' "$composer_json" | head -1)
  if [ -z "$ver" ]; then
    release_log_error "cannot parse version from $composer_json"
    return 1
  fi
  printf '%s' "$ver"
}

release_mode_to_product_model() {
  case "$1" in
    b2c) printf 'standard' ;;
    bbc) printf 'platform' ;;
    *) release_log_error "invalid mode: $1 (expected b2c|bbc)"; return 1 ;;
  esac
}

release_mode_to_dist_suffix() {
  case "$1" in
    b2c|bbc) printf '%s' "$1" ;;
    *) release_log_error "invalid mode: $1 (expected b2c|bbc)"; return 1 ;;
  esac
}

release_require_cmds() {
  local missing=0 c
  for c in "$@"; do
    if ! command -v "$c" >/dev/null 2>&1; then
      release_log_error "missing required command: $c"
      missing=1
    fi
  done
  [ "$missing" -eq 0 ]
}

# Load nvm into the current shell (required before release_nvm_use).
release_load_nvm() {
  export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
  if [ ! -s "$NVM_DIR/nvm.sh" ]; then
    release_log_error "nvm not found at ${NVM_DIR}/nvm.sh (needed to switch Node versions for pack.sh)"
    return 1
  fi
  # shellcheck disable=SC1090
  . "$NVM_DIR/nvm.sh"
}

# Install (if missing) and activate a Node version via nvm. Example: release_nvm_use 16.20
release_nvm_use() {
  local version=${1:-}
  if [ -z "$version" ]; then
    release_log_error "release_nvm_use requires a Node version (e.g. 16.20 or 20.19)"
    return 1
  fi
  release_load_nvm || return 1
  release_log_info "nvm: switching to Node ${version}"
  nvm install "$version"
  nvm use "$version"
  hash -r 2>/dev/null || true
  release_log_info "active node $(node -v); npm $(npm -v)"
}

# Ensure a pnpm version that works with the currently active Node (avoid global pnpm that requires Node 22+).
release_ensure_pnpm() {
  local want=${1:-10.13.0}
  if command -v corepack >/dev/null 2>&1; then
    corepack enable >/dev/null 2>&1 || true
    corepack prepare "pnpm@${want}" --activate
  else
    npm install -g "pnpm@${want}"
  fi
  hash -r 2>/dev/null || true
  if ! command -v pnpm >/dev/null 2>&1; then
    release_log_error "pnpm@${want} is not available after install under $(node -v)"
    return 1
  fi
  release_log_info "active pnpm $(pnpm -v) (requested ${want})"
}
