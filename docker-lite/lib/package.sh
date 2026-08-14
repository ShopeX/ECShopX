#!/usr/bin/env bash

release_archive_basename() {
  printf 'ecshopx-%s' "$1"
}

release_build_tarball() {
  local version=$1
  local output_dir=$2
  local name stage exclude_file
  name=$(release_archive_basename "$version")
  mkdir -p "$output_dir"
  stage=$(mktemp -d)
  exclude_file=$(mktemp)

  cat > "$exclude_file" <<'EOF'
/node_modules
.git
*.log
.DS_Store
._*
dist-release
ecshopx-*.tar.gz
.env
EOF

  mkdir -p "$stage/$name"
  # Copy four projects; --exclude from file via rsync if available
  local projects=(ECShopX ECShopX_admin-frontend ECShopX_mobile-frontend ECShopX_web-frontend)
  local p
  for p in "${projects[@]}"; do
    if [ ! -d "$RELEASE_PARENT_DIR/$p" ]; then
      release_log_error "missing project for package: $RELEASE_PARENT_DIR/$p"
      rm -rf "$stage" "$exclude_file"
      return 1
    fi
    if command -v rsync >/dev/null 2>&1; then
      mkdir -p "$stage/$name/$p"
      rsync -a --exclude-from="$exclude_file" \
        "$RELEASE_PARENT_DIR/$p"/ "$stage/$name/$p"/
    else
      cp -a "$RELEASE_PARENT_DIR/$p" "$stage/$name/$p"
      rm -rf "$stage/$name/$p/node_modules" "$stage/$name/$p/.git"
      rm -f "$stage/$name/$p/.env"
      rm -f "$stage/$name/$p"/ecshopx-*.tar.gz
    fi
  done

  # Drop transient admin/mobile dist (keep dist-b2c / dist-bbc only)
  rm -rf "$stage/$name/ECShopX_admin-frontend/dist"
  rm -rf "$stage/$name/ECShopX_mobile-frontend/dist"

  # Also place demo SQL under docker-new/demo for deploy.sh compatibility
  if [ -d "$RELEASE_ECSHOPX_ROOT/docker-dev/demo" ]; then
    mkdir -p "$stage/$name/ECShopX/docker-new/demo"
    if command -v rsync >/dev/null 2>&1; then
      rsync -a "$RELEASE_ECSHOPX_ROOT/docker-dev/demo/" "$stage/$name/ECShopX/docker-new/demo/"
    else
      cp -a "$RELEASE_ECSHOPX_ROOT/docker-dev/demo/." "$stage/$name/ECShopX/docker-new/demo/"
    fi
  fi

  if [ ! -f "$stage/$name/ECShopX/docker-lite/Dockerfile" ]; then
    release_log_error "docker-lite/Dockerfile missing from package staging tree"
    rm -rf "$stage" "$exclude_file"
    return 1
  fi

  if [ ! -f "$stage/$name/ECShopX/docker-lite/docker-compose.yml" ]; then
    release_log_error "docker-lite/docker-compose.yml missing from package staging tree"
    rm -rf "$stage" "$exclude_file"
    return 1
  fi

  # docker-new is optional for release runtime (kept only if present for demo SQL copy target)
  if [ ! -f "$stage/$name/ECShopX/docker-new/Dockerfile" ]; then
    release_log_info "docker-new/Dockerfile not in package (OK for mount-based release)"
  fi

  # Drop macOS AppleDouble leftovers that break Alpine supervisord / tools
  find "$stage/$name" -name '._*' -delete 2>/dev/null || true

  COPYFILE_DISABLE=1 tar -czf "$output_dir/$name.tar.gz" -C "$stage" "$name"
  rm -rf "$stage" "$exclude_file"
  release_log_success "wrote $output_dir/$name.tar.gz"
}
