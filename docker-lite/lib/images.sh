#!/usr/bin/env bash
# Image tar download / docker load helpers for docker-lite/

release_images_dir() {
  printf '%s' "${RELEASE_LITE_DIR}/images"
}

release_images_env_file() {
  printf '%s' "${RELEASE_LITE_DIR}/images.env"
}

release_load_images_env() {
  local env_file
  env_file=$(release_images_env_file)
  if [ ! -f "$env_file" ]; then
    if [ -f "${env_file}.example" ]; then
      release_log_info "创建 docker-lite/images.env from example"
      cp "${env_file}.example" "$env_file"
    else
      release_log_error "missing $env_file (and .example)"
      return 1
    fi
  fi
  # shellcheck disable=SC1090
  set -a
  # shellcheck disable=SC1091
  . "$env_file"
  set +a
}

release_image_tar_basename() {
  local url_or_path=$1
  local base
  base=$(basename "${url_or_path%%\?*}")
  if [ -z "$base" ] || [ "$base" = "/" ]; then
    release_log_error "cannot derive tar filename from: $url_or_path"
    return 1
  fi
  printf '%s' "$base"
}

# Download URL to dest if dest missing. Uses curl or wget.
release_download_file() {
  local url=$1
  local dest=$2
  if [ -f "$dest" ]; then
    release_log_info "已存在，跳过下载: $dest"
    return 0
  fi
  if [ -z "$url" ]; then
    release_log_error "empty download URL for $dest"
    return 1
  fi
  mkdir -p "$(dirname "$dest")"
  local tmp="${dest}.partial"
  release_log_info "下载镜像包: $url"
  if command -v curl >/dev/null 2>&1; then
    curl -fL --retry 3 --retry-delay 2 -o "$tmp" "$url"
  elif command -v wget >/dev/null 2>&1; then
    wget -O "$tmp" "$url"
  else
    release_log_error "需要 curl 或 wget 下载镜像 tar"
    return 1
  fi
  mv "$tmp" "$dest"
  release_log_success "已下载: $dest"
}

# docker load a tar; optional expected_ref is informational (tag must already be in the tar)
release_docker_load_tar() {
  local tar_path=$1
  if [ ! -f "$tar_path" ]; then
    release_log_error "镜像 tar 不存在: $tar_path"
    return 1
  fi
  release_log_info "docker load < $tar_path"
  if ! docker load -i "$tar_path"; then
    release_log_error "docker load 失败: $tar_path"
    return 1
  fi
  release_log_success "已导入: $tar_path"
}

# Ensure image ref exists locally.
# Order: local image → images/*.tar (download if URL) → docker pull from registry.
# Args: image_ref tar_url local_tar_name(optional)
release_ensure_image() {
  local image_ref=$1
  local tar_url=${2:-}
  local tar_name=${3:-}

  if [ -z "$image_ref" ]; then
    release_log_error "release_ensure_image requires image ref"
    return 1
  fi

  if docker image inspect "$image_ref" >/dev/null 2>&1; then
    release_log_info "本地已有镜像: $image_ref"
    return 0
  fi

  local images_dir tar_path
  images_dir=$(release_images_dir)
  mkdir -p "$images_dir"

  if [ -z "$tar_name" ] && [ -n "$tar_url" ]; then
    tar_name=$(release_image_tar_basename "$tar_url") || return 1
  fi

  if [ -n "$tar_name" ]; then
    tar_path="${images_dir}/${tar_name}"
    if [ ! -f "$tar_path" ]; then
      if [ -z "$tar_url" ]; then
        release_log_error "缺少本地文件 $tar_path，且未配置下载 URL（镜像: $image_ref）"
        return 1
      fi
      release_download_file "$tar_url" "$tar_path" || return 1
    fi

    release_docker_load_tar "$tar_path" || return 1

    if ! docker image inspect "$image_ref" >/dev/null 2>&1; then
      release_log_error "docker load 后仍找不到镜像 $image_ref（请确认 tar 内 tag 与 images.env 中配置一致）"
      return 1
    fi
    return 0
  fi

  release_log_info "本地无镜像且无 tar，尝试 docker pull: $image_ref"
  if ! docker pull "$image_ref"; then
    release_log_error "docker pull 失败: $image_ref（可配置 TAR URL，或先 docker pull / docker load）"
    return 1
  fi
  if ! docker image inspect "$image_ref" >/dev/null 2>&1; then
    release_log_error "docker pull 后仍找不到镜像 $image_ref"
    return 1
  fi
  release_log_success "已拉取: $image_ref"
}

release_ensure_runtime_images() {
  release_load_images_env || return 1

  release_ensure_image "${APP_BASE_IMAGE:?APP_BASE_IMAGE required in images.env}" "${APP_BASE_IMAGE_TAR_URL:-}" || return 1
  release_ensure_image "${MYSQL_IMAGE:?MYSQL_IMAGE required in images.env}" "${MYSQL_IMAGE_TAR_URL:-}" || return 1
  release_ensure_image "${REDIS_IMAGE:?REDIS_IMAGE required in images.env}" "${REDIS_IMAGE_TAR_URL:-}" || return 1
}

# Download image tars into docker-lite/images/ (used by pack.sh --with-images)
release_prefetch_image_tars() {
  release_load_images_env || return 1
  local images_dir
  images_dir=$(release_images_dir)
  mkdir -p "$images_dir"

  local url name
  for url in "${APP_BASE_IMAGE_TAR_URL:-}" "${MYSQL_IMAGE_TAR_URL:-}" "${REDIS_IMAGE_TAR_URL:-}"; do
    if [ -z "$url" ]; then
      continue
    fi
    name=$(release_image_tar_basename "$url") || return 1
    release_download_file "$url" "${images_dir}/${name}" || return 1
  done
}
