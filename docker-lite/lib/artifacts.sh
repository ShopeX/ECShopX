#!/usr/bin/env bash

release_sync_dir() {
  local src=$1 dest=$2
  if [ ! -d "$src" ]; then
    release_log_error "source artifact dir missing: $src"
    return 1
  fi
  rm -rf "$dest"
  mkdir -p "$(dirname "$dest")"
  if command -v rsync >/dev/null 2>&1; then
    mkdir -p "$dest"
    rsync -a --delete "$src"/ "$dest"/
  else
    cp -a "$src" "$dest"
  fi
}

release_validate_prebuilt_artifacts() {
  local ok=0
  local checks=(
    "$RELEASE_ECSHOPX_ROOT/vendor/autoload.php"
    "$RELEASE_ADMIN_DIR/dist-b2c/index.html"
    "$RELEASE_ADMIN_DIR/dist-bbc/index.html"
    "$RELEASE_MOBILE_DIR/dist-b2c/h5"
    "$RELEASE_MOBILE_DIR/dist-bbc/h5"
    "$RELEASE_PC_DIR/.output/server/index.mjs"
  )
  local p
  for p in "${checks[@]}"; do
    if [ ! -e "$p" ]; then
      release_log_error "missing prebuilt artifact: $p"
      ok=1
    fi
  done
  return "$ok"
}

release_activate_frontend_dist() {
  local mode=$1
  local suffix
  suffix=$(release_mode_to_dist_suffix "$mode") || return 1
  release_sync_dir "$RELEASE_ADMIN_DIR/dist-$suffix" "$RELEASE_ADMIN_DIR/dist" || return 1
  release_sync_dir "$RELEASE_MOBILE_DIR/dist-$suffix" "$RELEASE_MOBILE_DIR/dist" || return 1
  if [ ! -f "$RELEASE_ADMIN_DIR/dist/index.html" ]; then
    release_log_error "admin dist activation failed (no index.html)"
    return 1
  fi
  if [ ! -d "$RELEASE_MOBILE_DIR/dist/h5" ]; then
    release_log_error "mobile dist activation failed (no dist/h5)"
    return 1
  fi
  release_log_success "activated frontend dist for mode=$mode"
}
