#!/usr/bin/env bash
set -euo pipefail

# Intentionally does NOT run: composer-install, npm-install, pnpm-install, or frontend build
# Runtime: docker-lite/docker-compose.yml (app + mysql + redis).
# MySQL/Redis (and optional app base) images come from docker load of tar packages.

SCRIPT_PATH="${BASH_SOURCE[0]}"
LITE_DIR="$(cd "$(dirname "$SCRIPT_PATH")" && pwd)"
# shellcheck source=lib/common.sh
source "$LITE_DIR/lib/common.sh"
# shellcheck source=lib/artifacts.sh
source "$LITE_DIR/lib/artifacts.sh"
# shellcheck source=lib/images.sh
source "$LITE_DIR/lib/images.sh"

release_init_paths "$SCRIPT_PATH"

CONTAINER_NAME="${CONTAINER_NAME:-ecshopx}"
MYSQL_CONTAINER_NAME="${MYSQL_CONTAINER_NAME:-ecshopx-mysql}"
COMPOSE_FILE="$RELEASE_LITE_DIR/docker-compose.yml"
APP_DIR_IN_CONTAINER="/data/httpd/ECShopX"
DOCKER_COMPOSE_CMD=""

MODE=""
SKIP_DEMO=false
SITE_URL=""
ADMIN_URL_OVERRIDE=""
H5_URL_OVERRIDE=""
PC_URL_OVERRIDE=""

ADMIN_URL="http://localhost:8080"
API_BASE_URL="http://localhost:8080/api/"
H5_URL="http://localhost:8081"
PC_URL="http://localhost:8082"
QIANKUN_ENTRY_URL="http://localhost:8080/newpc/"

trim_trailing_slashes() {
  local value=$1
  while [ "${value%/}" != "$value" ]; do
    value=${value%/}
  done
  printf '%s' "$value"
}

origin_from_url() {
  local url=$1
  local scheme=${url%%://*}
  local without_scheme=${url#*://}
  local authority=${without_scheme%%/*}
  printf '%s' "${scheme}://${authority}"
}

validate_public_url() {
  local name=$1 value=$2
  if [ -z "$value" ]; then
    return 0
  fi
  case "$value" in
    http://*|https://*) return 0 ;;
    *)
      release_log_error "$name 必须以 http:// 或 https:// 开头: $value"
      exit 1
      ;;
  esac
}

upsert_env_file() {
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
    echo "${key}=${value}" >>"$file"
  fi
}

usage() {
  cat <<'EOF'
Usage: ./deploy.sh --mode b2c|bbc [options]

Loads MySQL/Redis/(optional) base image tars, then starts app+mysql+redis via
docker-lite/docker-compose.yml. App code is bind-mounted (not baked into the image).

Options:
  --mode b2c|bbc       Business mode (required unless prompted)
  --site-url URL       Admin/API base
  --admin-url URL      Admin URL (default http://localhost:8080)
  --h5-url URL         H5 URL (default http://localhost:8081)
  --pc-url URL         PC URL (default http://localhost:8082)
  --skip-demo          Skip demo SQL import
  -h, --help           Show help

Env:
  ADMIN_PASSWORD       Non-interactive admin password (skips prompt)

Configure image tar URLs / tags in docker-lite/images.env
Place pre-downloaded tars in docker-lite/images/ to skip download.
EOF
}

derive_api_base_from_admin_url() {
  local normalized_api_url
  normalized_api_url="$(trim_trailing_slashes "$ADMIN_URL")/api"
  API_BASE_URL="${normalized_api_url}/"
}

set_url_defaults_from_site_url() {
  local normalized_site_url
  normalized_site_url=$(trim_trailing_slashes "$SITE_URL")
  ADMIN_URL="$normalized_site_url"
  QIANKUN_ENTRY_URL="${normalized_site_url}/newpc/"
  derive_api_base_from_admin_url
}

configure_public_urls() {
  SITE_URL=$(trim_trailing_slashes "$SITE_URL")
  ADMIN_URL_OVERRIDE=$(trim_trailing_slashes "$ADMIN_URL_OVERRIDE")
  H5_URL_OVERRIDE=$(trim_trailing_slashes "$H5_URL_OVERRIDE")
  PC_URL_OVERRIDE=$(trim_trailing_slashes "$PC_URL_OVERRIDE")

  validate_public_url "--site-url" "$SITE_URL"
  validate_public_url "--admin-url" "$ADMIN_URL_OVERRIDE"
  validate_public_url "--h5-url" "$H5_URL_OVERRIDE"
  validate_public_url "--pc-url" "$PC_URL_OVERRIDE"

  if [ -n "$SITE_URL" ]; then
    set_url_defaults_from_site_url
  fi
  if [ -n "$ADMIN_URL_OVERRIDE" ]; then
    ADMIN_URL="$ADMIN_URL_OVERRIDE"
    QIANKUN_ENTRY_URL="${ADMIN_URL}/newpc/"
  fi
  if [ -n "$H5_URL_OVERRIDE" ]; then
    H5_URL="$H5_URL_OVERRIDE"
  fi
  if [ -n "$PC_URL_OVERRIDE" ]; then
    PC_URL="$PC_URL_OVERRIDE"
  fi
  derive_api_base_from_admin_url
}

# PC Nuxt browser API uses relative /api/h5app (nginx on :8082 proxies).
# Decoration postMessage allowlist uses admin URL when provided.
sync_nuxt_public_env() {
  local env_file
  env_file=$(release_images_env_file)
  if [ ! -f "$env_file" ]; then
    return 0
  fi
  upsert_env_file "$env_file" "NUXT_PUBLIC_API_BASE" "/api/h5app"
  if [ -n "$ADMIN_URL" ]; then
    upsert_env_file "$env_file" "NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS" "$ADMIN_URL"
  fi
}

detect_docker_compose() {
  if docker compose version >/dev/null 2>&1; then
    DOCKER_COMPOSE_CMD="docker compose"
  elif command -v docker-compose >/dev/null 2>&1; then
    DOCKER_COMPOSE_CMD="docker-compose"
  else
    release_log_error "未找到 docker compose 或 docker-compose"
    exit 1
  fi
}

compose() {
  $DOCKER_COMPOSE_CMD \
    --env-file "$(release_images_env_file)" \
    -f "$COMPOSE_FILE" \
    --project-directory "$RELEASE_LITE_DIR" \
    "$@"
}

ensure_backend_env_file() {
  local env_file=$RELEASE_ECSHOPX_ROOT/.env
  if [ ! -f "$env_file" ]; then
    for template in "$RELEASE_ECSHOPX_ROOT/.env.full" "$RELEASE_ECSHOPX_ROOT/.env.example"; do
      if [ -f "$template" ]; then
        release_log_info "从 $(basename "$template") 创建 .env"
        cp "$template" "$env_file"
        break
      fi
    done
    touch "$env_file"
  fi
}

write_backend_env() {
  local product_model=$1
  local env_file=$RELEASE_ECSHOPX_ROOT/.env

  ensure_backend_env_file
  release_load_images_env || return 1

  # In-compose service DNS names
  upsert_env_file "$env_file" "DB_HOST" "mysql"
  upsert_env_file "$env_file" "DB_PORT" "3306"
  upsert_env_file "$env_file" "DB_DATABASE" "${MYSQL_DATABASE:-ecshopx}"
  upsert_env_file "$env_file" "DB_USERNAME" "${MYSQL_USER:-ecshopx}"
  upsert_env_file "$env_file" "DB_PASSWORD" "${MYSQL_PASSWORD:-ecshopx}"
  upsert_env_file "$env_file" "REDIS_HOST" "redis"
  upsert_env_file "$env_file" "REDIS_PORT" "6379"
  upsert_env_file "$env_file" "REDIS_PASSWORD" "${REDIS_PASSWORD:-redispassword}"
  upsert_env_file "$env_file" "REDIS_DATABASE" "0"
  upsert_env_file "$env_file" "DISK_DRIVER" "local"
  upsert_env_file "$env_file" "PRODUCT_MODEL" "$product_model"
  upsert_env_file "$env_file" "APP_URL" "$ADMIN_URL"
  upsert_env_file "$env_file" "H5_BASE_URL" "$H5_URL"
  upsert_env_file "$env_file" "SHOP_ADMIN_URL" "${ADMIN_URL}/"
  upsert_env_file "$env_file" "API_BASE_URL" "$API_BASE_URL"

  release_log_success "已更新 ECShopX/.env (PRODUCT_MODEL=$product_model, DB_HOST=mysql)"
}

container_app() {
  docker exec "$CONTAINER_NAME" sh -c "cd $APP_DIR_IN_CONTAINER && $*"
}

ensure_application_secrets() {
  local app_key jwt_secret
  app_key=$(docker exec "$CONTAINER_NAME" sh -c "cd $APP_DIR_IN_CONTAINER && grep '^APP_KEY=' .env | cut -d'=' -f2-" 2>/dev/null || true)
  if [ -z "$app_key" ]; then
    release_log_info "生成应用密钥..."
    container_app "php artisan key:generate --force" || return 1
  else
    release_log_info "应用密钥已存在，跳过生成"
  fi
  jwt_secret=$(docker exec "$CONTAINER_NAME" sh -c "cd $APP_DIR_IN_CONTAINER && grep '^JWT_SECRET=' .env | cut -d'=' -f2-" 2>/dev/null || true)
  if [ -z "$jwt_secret" ]; then
    release_log_info "生成 JWT 密钥..."
    container_app "php artisan jwt:secret --force" || return 1
  else
    release_log_info "JWT 密钥已存在，跳过生成"
  fi
}

deploy_run_migrations() {
  release_log_info "执行数据库迁移..."
  if container_app "php artisan doctrine:migrations:migrate --no-interaction"; then
    release_log_success "数据库迁移完成"
    return 0
  fi
  release_log_error "数据库迁移失败"
  return 1
}

# OpenResty workers run as www-data. The compose bind-mount maps RELEASE_PARENT_DIR →
# /data/httpd. If that host dir is mode 0750 (common for /root), www-data cannot traverse
# it and static admin/H5 pages return 404 even when dist/ exists.
deploy_ensure_bind_mount_traverse() {
  local parent="$RELEASE_PARENT_DIR"
  local d

  if [ ! -d "$parent" ]; then
    release_log_error "挂载根目录不存在: $parent"
    return 1
  fi

  release_log_info "确保挂载根目录可被容器内 www-data 遍历: $parent"
  if ! chmod a+x "$parent" 2>/dev/null; then
    release_log_warning "无法 chmod a+x $parent；若管理后台/H5 出现 404，请手动执行: chmod a+x $parent"
  else
    release_log_success "已设置挂载根目录遍历权限 (a+x)"
  fi

  for d in "$RELEASE_ECSHOPX_ROOT" "$RELEASE_ADMIN_DIR" "$RELEASE_MOBILE_DIR" "$RELEASE_PC_DIR"; do
    if [ -d "$d" ]; then
      chmod a+rx "$d" 2>/dev/null || true
    fi
  done

  # Capital X: add execute only on directories (and already-executable files)
  if [ -d "$RELEASE_ADMIN_DIR/dist" ]; then
    chmod -R a+rX "$RELEASE_ADMIN_DIR/dist" 2>/dev/null || true
  fi
  if [ -d "$RELEASE_MOBILE_DIR/dist" ]; then
    chmod -R a+rX "$RELEASE_MOBILE_DIR/dist" 2>/dev/null || true
  fi
  if [ -d "$RELEASE_PC_DIR/.output" ]; then
    chmod -R a+rX "$RELEASE_PC_DIR/.output" 2>/dev/null || true
  fi
  return 0
}

# Align ownership with php-fpm (www-data) before migrate / artisan writes.
deploy_fix_app_ownership() {
  release_log_info "修正 ECShopX 目录权限为 www-data:www-data..."
  if docker exec "$CONTAINER_NAME" chown -R www-data:www-data "$APP_DIR_IN_CONTAINER"; then
    release_log_success "目录权限已修正"
    return 0
  fi
  release_log_warning "修正目录权限失败，继续执行"
  return 0
}

deploy_storage_link() {
  release_log_info "创建存储目录链接 (storage:link)..."
  if docker exec "$CONTAINER_NAME" sh -c \
    "cd $APP_DIR_IN_CONTAINER && if [ ! -L public/storage ] && [ ! -d public/storage ]; then php artisan storage:link; fi"; then
    release_log_success "存储链接配置完成"
    return 0
  fi
  release_log_warning "存储链接创建失败，可稍后手动执行: php artisan storage:link"
  return 0
}

deploy_init_admin_password() {
  local admin_password="${ADMIN_PASSWORD:-}"
  local admin_password_confirm=""

  if [ -z "$admin_password" ]; then
    if [ ! -t 0 ] && [ ! -r /dev/tty ]; then
      release_log_warning "无交互终端且未设置 ADMIN_PASSWORD，跳过管理员密码初始化"
      return 0
    fi
    echo ""
    while true; do
      if [ -r /dev/tty ]; then
        read -r -s -p "请输入管理员密码: " admin_password </dev/tty
        echo ""
        read -r -s -p "请再次确认密码: " admin_password_confirm </dev/tty
        echo ""
      else
        read -r -s -p "请输入管理员密码: " admin_password || true
        echo ""
        read -r -s -p "请再次确认密码: " admin_password_confirm || true
        echo ""
      fi
      if [ -z "$admin_password" ]; then
        release_log_warning "密码不能为空，请重新输入"
        continue
      fi
      if [ "$admin_password" != "$admin_password_confirm" ]; then
        release_log_warning "两次输入的密码不一致，请重新输入"
        continue
      fi
      break
    done
  else
    release_log_info "使用环境变量 ADMIN_PASSWORD 初始化管理员密码"
  fi

  release_log_info "初始化管理员密码..."
  # Host expands password; remote sh sees a single-quoted argument (same as dev-setup.sh).
  if docker exec "$CONTAINER_NAME" sh -c \
    "cd $APP_DIR_IN_CONTAINER && php artisan account:init-admin-password '$admin_password'"; then
    release_log_success "管理员密码初始化完成"
    return 0
  fi
  release_log_warning "管理员密码初始化失败"
  return 0
}

deploy_init_aliyun_sms_scenes() {
  release_log_info "初始化阿里云短信场景..."
  if container_app "php artisan aliyunsms:scene:initialize 1"; then
    release_log_success "阿里云短信场景初始化完成"
    return 0
  fi
  release_log_warning "阿里云短信场景初始化失败"
  return 0
}

wait_for_mysql() {
  release_log_info "等待 MySQL 容器就绪..."
  local i
  for i in $(seq 1 60); do
    if docker exec "$MYSQL_CONTAINER_NAME" sh -c \
      "mysqladmin ping -h 127.0.0.1 -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --silent" 2>/dev/null ||
      docker exec "$MYSQL_CONTAINER_NAME" mysqladmin ping -h 127.0.0.1 -uroot -p"${MYSQL_ROOT_PASSWORD:-rootpassword}" --silent 2>/dev/null; then
      release_log_success "MySQL 已就绪"
      return 0
    fi
    sleep 2
    if [ $((i % 5)) -eq 0 ]; then
      release_log_info "  等待 MySQL... ($i/60)"
    fi
  done
  release_log_error "MySQL 启动超时"
  return 1
}

deploy_import_demo_data() {
  local product_model=$1
  local demo_dir_host="$RELEASE_ECSHOPX_ROOT/docker-new/demo"
  local sql_file="" sql_path=""

  case "$product_model" in
    platform) sql_file="bbc.sql" ;;
    standard) sql_file="b2c_sports.sql" ;;
    *)
      release_log_warning "未知 PRODUCT_MODEL，跳过 Demo 导入"
      return 0
      ;;
  esac

  sql_path="$demo_dir_host/$sql_file"
  if [ ! -f "$sql_path" ]; then
    release_log_warning "Demo 数据文件不存在: $sql_path（跳过）"
    return 0
  fi

  release_log_info "导入 Demo: $sql_file -> 容器 $MYSQL_CONTAINER_NAME ..."
  if ! docker exec -i "$MYSQL_CONTAINER_NAME" \
    mysql --default-character-set=utf8mb4 -uroot -p"${MYSQL_ROOT_PASSWORD:-rootpassword}" "${MYSQL_DATABASE:-ecshopx}" <"$sql_path"; then
    release_log_warning "Demo 数据导入失败（可稍后手动导入）"
    return 0
  fi
  release_log_success "Demo 数据导入完成: $sql_file"
}

compose_up() {
  local version=$1
  release_load_images_env || return 1
  export APP_IMAGE="${APP_IMAGE:-ecshopx:${version}}"

  release_log_info "docker compose build app ..."
  if ! compose build app; then
    release_log_error "compose build 失败"
    return 1
  fi
  release_log_info "docker compose up -d ..."
  if ! compose up -d; then
    release_log_error "compose up 失败"
    compose logs --tail=80 || true
    return 1
  fi
  if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
    release_log_error "应用容器 $CONTAINER_NAME 未运行"
    compose logs app --tail=80 || true
    return 1
  fi
  release_log_success "compose 服务已启动 (app/mysql/redis)"
}

prompt_mode_if_missing() {
  if [ -n "$MODE" ]; then
    return 0
  fi
  echo ""
  release_log_info "请选择业务模式："
  release_log_info "  1) b2c (standard)"
  release_log_info "  2) bbc (platform)"
  echo ""
  local choice=""
  if [ -t 0 ] && [ -r /dev/tty ]; then
    read -r -p "请输入选项 (1-2，默认: 1): " choice </dev/tty
  else
    read -r -p "请输入选项 (1-2，默认: 1): " choice || true
  fi
  choice=${choice:-1}
  case "$choice" in
    1|b2c|B2C) MODE="b2c" ;;
    2|bbc|BBC) MODE="bbc" ;;
    *)
      release_log_error "无效选项: $choice"
      exit 1
      ;;
  esac
}

parse_args() {
  while [ $# -gt 0 ]; do
    case "$1" in
      --mode) MODE="${2:-}"; shift 2 ;;
      --site-url) SITE_URL="${2:-}"; shift 2 ;;
      --admin-url) ADMIN_URL_OVERRIDE="${2:-}"; shift 2 ;;
      --h5-url) H5_URL_OVERRIDE="${2:-}"; shift 2 ;;
      --pc-url) PC_URL_OVERRIDE="${2:-}"; shift 2 ;;
      --skip-demo) SKIP_DEMO=true; shift ;;
      -h|--help) usage; exit 0 ;;
      *)
        release_log_error "未知参数: $1"
        usage >&2
        exit 1
        ;;
    esac
  done
}

main() {
  parse_args "$@"
  prompt_mode_if_missing

  case "$MODE" in
    b2c|bbc) ;;
    *)
      release_log_error "无效 --mode: $MODE (expected b2c|bbc)"
      exit 1
      ;;
  esac

  release_require_cmds docker || exit 1
  detect_docker_compose

  if [ ! -f "$COMPOSE_FILE" ]; then
    release_log_error "未找到 $COMPOSE_FILE"
    exit 1
  fi

  release_validate_prebuilt_artifacts || {
    release_log_error "预置产物不完整，请使用完整离线发行包或先执行 pack.sh"
    exit 1
  }

  local product_model version
  product_model=$(release_mode_to_product_model "$MODE") || exit 1
  version=$(release_read_product_version "$RELEASE_ECSHOPX_ROOT/composer.json") || exit 1

  configure_public_urls
  sync_nuxt_public_env
  release_activate_frontend_dist "$MODE" || exit 1
  write_backend_env "$product_model"
  mkdir -p "$RELEASE_ECSHOPX_ROOT/storage/logs" \
    "$RELEASE_ECSHOPX_ROOT/storage/framework/cache" \
    "$RELEASE_ECSHOPX_ROOT/storage/framework/cache/laravel-excel"

  release_log_info "准备运行时镜像（docker load tar）..."
  release_ensure_runtime_images || exit 1

  deploy_ensure_bind_mount_traverse || exit 1
  compose_up "$version" || exit 1
  ensure_application_secrets || exit 1
  wait_for_mysql || exit 1
  deploy_fix_app_ownership
  deploy_run_migrations || exit 1
  deploy_storage_link
  deploy_init_admin_password
  deploy_init_aliyun_sms_scenes
  if [ "$SKIP_DEMO" = false ]; then
    deploy_import_demo_data "$product_model" || release_log_warning "Demo 数据导入未完成"
  else
    release_log_info "已跳过 Demo 数据导入 (--skip-demo)"
  fi

  release_load_images_env || true
  release_log_success "部署完成"
  release_log_info "服务（docker compose -f docker-lite/docker-compose.yml）："
  release_log_info "  app    : $CONTAINER_NAME  (OpenResty/FPM/cron/queues/Nuxt)"
  release_log_info "  mysql  : $MYSQL_CONTAINER_NAME (宿主机端口 ${MYSQL_HOST_PORT:-3306})"
  release_log_info "  redis  : ecshopx-redis (宿主机端口 ${REDIS_HOST_PORT:-6379})"
  release_log_info "对外端口: 管理后台 ${ADMIN_HOST_PORT:-8080} | H5 ${H5_HOST_PORT:-8081} | PC ${PC_HOST_PORT:-8082}"
  release_log_info "容器内代码挂载（宿主机解压根目录 → /data/httpd）："
  release_log_info "    /data/httpd/ECShopX"
  release_log_info "    /data/httpd/ECShopX_admin-frontend"
  release_log_info "    /data/httpd/ECShopX_mobile-frontend"
  release_log_info "    /data/httpd/ECShopX_web-frontend"
  release_log_info "业务 URL: 管理后台 $ADMIN_URL | API $API_BASE_URL | H5 $H5_URL | PC $PC_URL"
}

main "$@"
