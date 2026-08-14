#!/usr/bin/env bash
set -euo pipefail

SCRIPT_PATH="${BASH_SOURCE[0]}"
LITE_DIR="$(cd "$(dirname "$SCRIPT_PATH")" && pwd)"
# shellcheck source=lib/common.sh
source "$LITE_DIR/lib/common.sh"
source "$LITE_DIR/lib/artifacts.sh"
source "$LITE_DIR/lib/package.sh"
source "$LITE_DIR/lib/images.sh"
release_init_paths "$SCRIPT_PATH"

# admin / mobile (Vue / Taro) vs PC (Nuxt) need different Node majors.
RELEASE_NODE_ADMIN_MOBILE="${RELEASE_NODE_ADMIN_MOBILE:-16.20}"
RELEASE_NODE_PC="${RELEASE_NODE_PC:-20.19}"
# Pin pnpm to the version declared by ECShopX_web-frontend (works on Node 20.19).
RELEASE_PNPM_VERSION="${RELEASE_PNPM_VERSION:-10.13.0}"
# H5 static image CDN (baked at build; most assets are not in the tarball)
APP_IMAGE_CDN="${APP_IMAGE_CDN:-https://ecshopx-vshop-images.oss-cn-shanghai.aliyuncs.com}"
WITH_IMAGES=false

set_env_kv() {
  local file=$1 key=$2 value=$3
  mkdir -p "$(dirname "$file")"
  touch "$file"
  if grep -q "^${key}=" "$file" 2>/dev/null; then
    if [[ "${OSTYPE:-}" == darwin* ]]; then
      sed -i '' "s|^${key}=.*|${key}=${value}|" "$file"
    else
      sed -i "s|^${key}=.*|${key}=${value}|" "$file"
    fi
  else
    echo "${key}=${value}" >> "$file"
  fi
}

prepare_release_envs() {
  # Same-origin under release nginx: /api → PHP. Must not be empty or clients hit the wrong host/path.
  set_env_kv "$RELEASE_ADMIN_DIR/.env" "VUE_APP_BASE_API" "/api"
  set_env_kv "$RELEASE_ADMIN_DIR/.env" "VUE_APP_DEFAULT_LANG" "zhcn"
  set_env_kv "$RELEASE_ADMIN_DIR/.env" "VUE_APP_PUBLIC_PATH" "/"
  # H5 Taro baseURL (baked at build): /api/h5app/wxapp + paths like /common/setting
  set_env_kv "$RELEASE_MOBILE_DIR/.env" "APP_BASE_URL" "/api/h5app/wxapp"
  set_env_kv "$RELEASE_MOBILE_DIR/.env" "APP_DEFAULT_LANGUAGE" "zhcn"
  set_env_kv "$RELEASE_MOBILE_DIR/.env" "APP_I18N_ORIGIN_LANG" "zhcn"
  # Must be present for defineConstants; missing APP_PUBLIC_PATH leaves process.env in H5 bundle
  set_env_kv "$RELEASE_MOBILE_DIR/.env" "APP_PUBLIC_PATH" "/"
  # Static image CDN (majority of H5 images are not shipped in the package)
  set_env_kv "$RELEASE_MOBILE_DIR/.env" "APP_IMAGE_CDN" "$APP_IMAGE_CDN"
  set_env_kv "$RELEASE_PC_DIR/.env" "NUXT_PUBLIC_DEFAULT_COUNTRY_CODE" "zh-CN"
  # PC public API (paths already include /wxapp/...); 8082 nginx proxies /api → PHP
  set_env_kv "$RELEASE_PC_DIR/.env" "NUXT_PUBLIC_API_BASE" "/api/h5app"
}

move_dist_to() {
  local project_dir=$1 dest_name=$2
  rm -rf "$project_dir/$dest_name"
  if [ ! -d "$project_dir/dist" ]; then
    release_log_error "expected $project_dir/dist after build"
    return 1
  fi
  mv "$project_dir/dist" "$project_dir/$dest_name"
}

main() {
  while [ $# -gt 0 ]; do
    case "$1" in
      --with-images)
        WITH_IMAGES=true
        shift
        ;;
      -h|--help)
        cat <<'EOF'
Usage: ./pack.sh [--with-images]

  --with-images   Download image tars from docker-lite/images.env URLs
                  into docker-lite/images/ and include them in the archive
EOF
        exit 0
        ;;
      *)
        release_log_error "unknown argument: $1"
        exit 1
        ;;
    esac
  done

  # pnpm is provisioned after switching to Node 20.19 (not required globally at start)
  release_require_cmds php node npm tar || exit 1
  release_load_nvm || exit 1

  for d in "$RELEASE_ADMIN_DIR" "$RELEASE_MOBILE_DIR" "$RELEASE_PC_DIR"; do
    if [ ! -d "$d" ]; then
      release_log_error "missing project dir: $d"
      exit 1
    fi
  done

  if [ ! -f "$RELEASE_LITE_DIR/docker-compose.yml" ]; then
    release_log_error "missing docker-lite/docker-compose.yml"
    exit 1
  fi

  local version
  version=$(release_read_product_version "$RELEASE_ECSHOPX_ROOT/composer.json")
  release_log_info "packing version $version"

  prepare_release_envs

  if [ "$WITH_IMAGES" = true ]; then
    release_log_info "prefetch runtime image tars (--with-images)"
    release_prefetch_image_tars || exit 1
  fi

  release_log_info "composer install"
  (
    cd "$RELEASE_ECSHOPX_ROOT"
    if [ -f composer.phar ]; then
      php -d memory_limit=-1 composer.phar install -o --no-interaction --prefer-dist
    else
      release_require_cmds composer || exit 1
      composer install -o --no-interaction --prefer-dist
    fi
  )

  release_log_info "build admin b2c/bbc (Node ${RELEASE_NODE_ADMIN_MOBILE})"
  (
    release_nvm_use "$RELEASE_NODE_ADMIN_MOBILE" || exit 1
    cd "$RELEASE_ADMIN_DIR"
    npm install --legacy-peer-deps
    npm run build:b2c
    move_dist_to "$RELEASE_ADMIN_DIR" dist-b2c
    npm run build:bbc
    move_dist_to "$RELEASE_ADMIN_DIR" dist-bbc
  )

  release_log_info "build mobile b2c/bbc (Node ${RELEASE_NODE_ADMIN_MOBILE}, APP_IMAGE_CDN=${APP_IMAGE_CDN})"
  (
    release_nvm_use "$RELEASE_NODE_ADMIN_MOBILE" || exit 1
    cd "$RELEASE_MOBILE_DIR"
    npm install --legacy-peer-deps
    APP_PLATFORM=standard APP_IMAGE_CDN="$APP_IMAGE_CDN" npm run build:h5
    move_dist_to "$RELEASE_MOBILE_DIR" dist-b2c
    APP_PLATFORM=platform APP_IMAGE_CDN="$APP_IMAGE_CDN" npm run build:h5
    move_dist_to "$RELEASE_MOBILE_DIR" dist-bbc
  )

  release_log_info "build PC (Node ${RELEASE_NODE_PC}, pnpm ${RELEASE_PNPM_VERSION})"
  (
    release_nvm_use "$RELEASE_NODE_PC" || exit 1
    release_ensure_pnpm "$RELEASE_PNPM_VERSION" || exit 1
    cd "$RELEASE_PC_DIR"
    # Non-interactive: skip Nuxt telemetry prompt; allow needed native dep build scripts under pnpm 10+
    export NUXT_TELEMETRY_DISABLED=1
    export CI=true
    pnpm install --config.dangerouslyAllowAllBuilds=true
    pnpm build
  )

  release_validate_prebuilt_artifacts || exit 1

  local out="$RELEASE_ECSHOPX_ROOT"
  release_build_tarball "$version" "$out"
  release_log_success "done: $out/ecshopx-${version}.tar.gz"
}

main "$@"
