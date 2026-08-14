#!/bin/bash
# 打包 ECShopX 宝塔一键部署 zip（后端 API + 前端静态资源）
#
# 宝塔「一键部署」要求 zip 根目录包含 auto_install.json、install.sh、nginx.rewrite。
# 本脚本在打包阶段把与服务器无关的重活烘焙进 zip：
#   - composer install --no-dev → vendor/
#   - 管理后台 dist → public/admin/（需 VUE_APP_PUBLIC_PATH=/admin/）
#   - H5 dist/h5 → public/mobile/（需 APP_PUBLIC_PATH=/mobile/）
#   - PC Nuxt SSR .output → web/.output/（需 NUXT_APP_BASE_URL=/web/）
#
# 打包机 Node 版本（通过 nvm 切换，需预先安装 nvm 及对应 Node）：
#   admin/mobile → PACK_NODE_ADMIN_MOBILE（默认 16.20）
#   PC Nuxt      → PACK_NODE_PC（默认 20.19）+ pnpm（PACK_PNPM_VERSION，默认 10.13.0）
# 可选 WEB_NODE_BIN 覆盖 PC 构建的 node（跳过 nvm，仅 PC）。
#
# 用法（在 ECShopX 项目根目录执行）：
#   bash baota/pack.sh [输出目录]
#
# 默认一次产出两个 zip（BBC + B2C）：
#   ecshopx-{version}-bbc-baota.zip  （PRODUCT_MODEL=platform，Demo: bbc.sql）
#   ecshopx-{version}-b2c-baota.zip  （PRODUCT_MODEL=standard，Demo: b2c_sports.sql）
#
# 仅打单包时显式设置 PACK_PLATFORM=platform|standard。
#
# 可选环境变量：
#   PACK_COMPOSER=false          不预装 composer
#   PACK_FRONTEND=false          不打包前端
#   PACK_FRONTEND_BUILD=false    不重新编译，直接使用已有 dist（默认会编译）
#   PACK_PLATFORM=platform       单包模式：platform(BBC) / standard(B2C)
#   （未设 PACK_PLATFORM 时默认双包 bbc+b2c）
#   PACK_DEFAULT_LANG=zhcn       默认语言：zhcn / en
#   PACK_ADMIN_SCRIPT=...        覆盖管理后台构建脚本（仅单包模式生效）
#   ADMIN_FRONTEND_DIR=...       管理后台仓库路径（默认 ../ECShopX_admin-frontend）
#   MOBILE_FRONTEND_DIR=...      H5 仓库路径（默认 ../ECShopX_mobile-frontend）
#   WEB_FRONTEND_DIR=...         PC Nuxt 仓库路径（默认 ../ECShopX_web-frontend）
#   PACK_NODE_ADMIN_MOBILE=16.20 admin/mobile 构建 Node 版本（nvm）
#   PACK_NODE_PC=20.19           PC 构建 Node 版本（nvm）
#   PACK_PNPM_VERSION=10.13.0    PC 构建 pnpm 版本
#   WEB_NODE_BIN=...             PC 构建用 Node 20+ 可执行文件（可选，设则跳过 nvm）
#   APP_IMAGE_CDN=...            H5 图片 CDN（默认阿里云 OSS，多数图不在包内）

set -e

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BAOTA_DIR="$PROJECT_ROOT/baota"
PARENT_DIR="$(cd "$PROJECT_ROOT/.." && pwd)"
OUT_DIR="${1:-$PROJECT_ROOT}"
TMP_BASE="$(mktemp -d)"

log() { echo "[pack] $*"; }
die() { echo "[pack][错误] $*" >&2; exit 1; }

PACK_NODE_ADMIN_MOBILE="${PACK_NODE_ADMIN_MOBILE:-16.20}"
PACK_NODE_PC="${PACK_NODE_PC:-20.19}"
PACK_PNPM_VERSION="${PACK_PNPM_VERSION:-10.13.0}"

load_nvm() {
    export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
    [ -s "$NVM_DIR/nvm.sh" ] || die "未找到 nvm: ${NVM_DIR}/nvm.sh。请安装 nvm 并准备 Node ${PACK_NODE_ADMIN_MOBILE} 与 ${PACK_NODE_PC}。"
    # shellcheck disable=SC1090
    . "$NVM_DIR/nvm.sh"
}

nvm_use() {
    local version="$1"
    load_nvm
    log "nvm: 切换到 Node ${version}"
    nvm install "$version"
    nvm use "$version"
    hash -r 2>/dev/null || true
    log "当前 node $(node -v); npm $(npm -v)"
}

ensure_pnpm() {
    local want="${1:-$PACK_PNPM_VERSION}"
    if command -v corepack >/dev/null 2>&1; then
        corepack enable >/dev/null 2>&1 || true
        corepack prepare "pnpm@${want}" --activate
    else
        npm install -g "pnpm@${want}"
    fi
    hash -r 2>/dev/null || true
    command -v pnpm >/dev/null 2>&1 || die "pnpm@${want} 不可用（当前 node $(node -v)）"
    log "当前 pnpm $(pnpm -v)"
}

PACK_COMPOSER="${PACK_COMPOSER:-true}"
PACK_FRONTEND="${PACK_FRONTEND:-true}"
PACK_FRONTEND_BUILD="${PACK_FRONTEND_BUILD:-true}"
PACK_DEFAULT_LANG="${PACK_DEFAULT_LANG:-zhcn}"
# H5 静态图 CDN（多数图不在包内，默认走阿里云 OSS）
APP_IMAGE_CDN="${APP_IMAGE_CDN:-https://ecshopx-vshop-images.oss-cn-shanghai.aliyuncs.com}"
ADMIN_FRONTEND_DIR="${ADMIN_FRONTEND_DIR:-$PARENT_DIR/ECShopX_admin-frontend}"
MOBILE_FRONTEND_DIR="${MOBILE_FRONTEND_DIR:-$PARENT_DIR/ECShopX_mobile-frontend}"
WEB_FRONTEND_DIR="${WEB_FRONTEND_DIR:-$PARENT_DIR/ECShopX_web-frontend}"

VENDOR_CACHE=""
STAGE=""

cleanup() { rm -rf "$TMP_BASE"; }
trap cleanup EXIT

# ---------------------------------------------------------------------------
# 读取 composer.json version
# ---------------------------------------------------------------------------
read_version() {
    local composer_json="$PROJECT_ROOT/composer.json"
    [ -f "$composer_json" ] || die "缺少 $composer_json"
    if command -v php >/dev/null 2>&1; then
        VERSION="$(
            php -r '
                $j = json_decode(file_get_contents($argv[1]), true);
                $v = is_array($j) ? ($j["version"] ?? "") : "";
                if ($v === "") { fwrite(STDERR, "missing version\n"); exit(1); }
                echo $v;
            ' "$composer_json" 2>/dev/null
        )" && [ -n "$VERSION" ] && return 0
    fi
    if command -v python3 >/dev/null 2>&1; then
        VERSION="$(
            python3 -c "import json,sys; j=json.load(open(sys.argv[1])); v=j.get('version',''); sys.exit(1) if not v else None; print(v)" \
                "$composer_json" 2>/dev/null
        )" && [ -n "$VERSION" ] && return 0
    fi
    VERSION="$(
        grep -E '"version"' "$composer_json" | head -1 \
            | sed -n 's/.*"version"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p'
    )"
    [ -n "$VERSION" ] || die "composer.json 缺少 version 字段。"
}

read_version

# ---------------------------------------------------------------------------
# 确定打包模式列表
# ---------------------------------------------------------------------------
MODES=()
if [ -n "${PACK_PLATFORM+x}" ]; then
    case "$PACK_PLATFORM" in
        platform) MODES=(bbc) ;;
        standard) MODES=(b2c) ;;
        *) die "PACK_PLATFORM 仅支持 platform 或 standard，当前: $PACK_PLATFORM" ;;
    esac
else
    MODES=(bbc b2c)
fi

mode_platform() {
    case "$1" in bbc) echo platform ;; b2c) echo standard ;; esac
}
mode_admin_script() {
    case "$1" in bbc) echo build:bbc ;; b2c) echo build:b2c ;; esac
}
mode_demo_sql() {
    case "$1" in bbc) echo bbc.sql ;; b2c) echo b2c_sports.sql ;; esac
}
mode_business_mode() {
    case "$1" in bbc) echo bbc ;; b2c) echo b2c ;; esac
}
# PRODUCT_MODEL 与 APP_PLATFORM 取值相同（platform|standard）
mode_product_model() { mode_platform "$1"; }

log "项目根目录: $PROJECT_ROOT"
log "版本: $VERSION"
log "输出目录: $OUT_DIR"
log "打包模式: ${MODES[*]}"

# ---------------------------------------------------------------------------
# 复制后端源码（不含 vendor / 前端产物）
# ---------------------------------------------------------------------------
rsync_backend() {
    rsync -a --quiet \
        --exclude '.git' \
        --exclude '.cursor' \
        --exclude '.claude' \
        --exclude 'node_modules' \
        --exclude 'vendor' \
        --exclude 'tests' \
        --exclude 'baota' \
        --exclude 'tdd-guard' \
        --exclude 'todo' \
        --exclude 'xhprof' \
        --exclude 'docker-dev' \
        --exclude 'docker-new' \
        --exclude 'docker-compose.dev.yml' \
        --exclude 'dev-setup.sh' \
        --exclude 'storage/logs/*' \
        --exclude 'ecshopx*.zip' \
        --exclude '.DS_Store' \
        --exclude 'public/admin' \
        --exclude 'public/mobile' \
        --exclude 'web' \
        "$PROJECT_ROOT/" "$STAGE/"
}

# ---------------------------------------------------------------------------
# composer 依赖（仅首次构建，后续 rsync vendor）
# ---------------------------------------------------------------------------
pack_composer() {
    [ "$PACK_COMPOSER" = "true" ] || { log "PACK_COMPOSER=false，跳过 composer 预装。"; return 0; }
    local php_bin composer_run
    php_bin="$(command -v php || true)"
    [ -z "$php_bin" ] && die "未找到 php，无法预装 composer 依赖（可设 PACK_COMPOSER=false 跳过）。"
    if [ -f "$STAGE/composer.phar" ]; then
        composer_run="$php_bin $STAGE/composer.phar"
    elif command -v composer >/dev/null 2>&1; then
        composer_run="$php_bin $(command -v composer)"
    else
        die "未找到 composer / composer.phar。"
    fi
    log "预装 composer 依赖（--no-dev）..."
    ( cd "$STAGE" \
        && $composer_run config repo.packagist composer https://mirrors.aliyun.com/composer/ >/dev/null 2>&1 \
        && $composer_run install --no-dev -o --no-interaction --prefer-dist --no-scripts ) \
        || die "composer install 失败。"
    VENDOR_CACHE="$STAGE/vendor"
    log "composer 依赖已烘焙进包。"
}

copy_vendor_cache() {
    [ -n "$VENDOR_CACHE" ] || return 0
    log "复用已构建的 vendor..."
    rsync -a --quiet "$VENDOR_CACHE/" "$STAGE/vendor/"
}

# ---------------------------------------------------------------------------
# Demo SQL（每包仅一个文件）
# ---------------------------------------------------------------------------
pack_demo_sql() {
    local demo_file="$1"
    local demo_src="$PROJECT_ROOT/docker-dev/demo/$demo_file"
    [ -f "$demo_src" ] || die "缺少 Demo 数据文件: $demo_src"
    mkdir -p "$STAGE/docker-dev/demo"
    rm -f "$STAGE/docker-dev/demo/"*.sql 2>/dev/null || true
    cp "$demo_src" "$STAGE/docker-dev/demo/$demo_file"
    log "已打入 Demo: docker-dev/demo/$demo_file"
}

# ---------------------------------------------------------------------------
# 前端构建
# ---------------------------------------------------------------------------
web_node_major() {
  local node_bin="${1:-node}"
  "$node_bin" -v 2>/dev/null | sed -n 's/^v\([0-9]*\).*/\1/p'
}

build_admin_frontend() {
    local admin_script="$1"
    log "编译管理后台 (${admin_script}, lang=${PACK_DEFAULT_LANG}, VUE_APP_PUBLIC_PATH=/admin/)..."
    (
        nvm_use "$PACK_NODE_ADMIN_MOBILE" || exit 1
        cd "$ADMIN_FRONTEND_DIR" \
        && npm config set registry https://registry.npmmirror.com >/dev/null 2>&1 || true \
        && npm install --legacy-peer-deps \
        && VUE_APP_PUBLIC_PATH=/admin/ \
           VUE_APP_BASE_API=/api \
           VUE_APP_DEFAULT_LANG="$PACK_DEFAULT_LANG" \
           npm run "$admin_script"
    ) || die "管理后台编译失败。"
}

build_mobile_frontend() {
    local platform="$1"
    log "编译 H5 (build:h5, APP_PLATFORM=${platform}, lang=${PACK_DEFAULT_LANG}, APP_PUBLIC_PATH=/mobile/, APP_IMAGE_CDN=${APP_IMAGE_CDN})..."
    (
        nvm_use "$PACK_NODE_ADMIN_MOBILE" || exit 1
        cd "$MOBILE_FRONTEND_DIR" \
        && npm config set registry https://registry.npmmirror.com >/dev/null 2>&1 || true \
        && npm install --legacy-peer-deps \
        && APP_PUBLIC_PATH=/mobile/ \
           APP_ROUTER_BASENAME=/mobile \
           APP_BASE_URL=/api/h5app/wxapp/ \
           APP_IMAGE_CDN="$APP_IMAGE_CDN" \
           APP_PLATFORM="$platform" \
           APP_I18N_ORIGIN_LANG="$PACK_DEFAULT_LANG" \
           npm run build:h5
    ) || die "H5 编译失败。"
}

build_web_frontend() {
    local business_mode="$1"
    log "编译 PC Nuxt (NUXT_APP_BASE_URL=/web/, NUXT_PUBLIC_API_BASE=/api/h5app, NUXT_PUBLIC_BUSINESS_MODE=${business_mode})..."

    if [ -n "${WEB_NODE_BIN:-}" ]; then
        local node_bin="$WEB_NODE_BIN" major pnpm_cmd
        [ -x "$node_bin" ] || die "WEB_NODE_BIN 不可执行: $node_bin"
        major="$(web_node_major "$node_bin")"
        [ -n "$major" ] && [ "$major" -ge 20 ] 2>/dev/null \
            || die "PC 构建需要 Node 20+，当前: $("$node_bin" -v 2>/dev/null || echo '未知')。"
        pnpm_cmd="$(dirname "$node_bin")/pnpm"
        [ -x "$pnpm_cmd" ] || pnpm_cmd="$(command -v pnpm || true)"
        [ -n "$pnpm_cmd" ] || die "未找到 pnpm（WEB_NODE_BIN 覆盖模式）。"
        (
            cd "$WEB_FRONTEND_DIR" \
            && export PATH="$(dirname "$node_bin"):$PATH" \
            && "$node_bin" --version >/dev/null \
            && "$pnpm_cmd" install \
            && NUXT_APP_BASE_URL=/web/ \
               NUXT_PUBLIC_API_BASE=/api/h5app \
               NUXT_PUBLIC_BUSINESS_MODE="$business_mode" \
               "$pnpm_cmd" build
        ) || die "PC Nuxt 编译失败。"
        return 0
    fi

    (
        nvm_use "$PACK_NODE_PC" || exit 1
        ensure_pnpm "$PACK_PNPM_VERSION" || exit 1
        local major
        major="$(web_node_major node)"
        [ -n "$major" ] && [ "$major" -ge 20 ] 2>/dev/null \
            || die "PC 构建需要 Node 20+，当前: $(node -v 2>/dev/null || echo '未知')。"
        cd "$WEB_FRONTEND_DIR" \
        && pnpm install \
        && NUXT_APP_BASE_URL=/web/ \
           NUXT_PUBLIC_API_BASE=/api/h5app \
           NUXT_PUBLIC_BUSINESS_MODE="$business_mode" \
           pnpm build
    ) || die "PC Nuxt 编译失败。"
}

assert_web_output() {
    local index_mjs="$WEB_FRONTEND_DIR/.output/server/index.mjs"
    [ -f "$index_mjs" ] || die "缺少 PC SSR 产物 ${index_mjs} (可设 PACK_FRONTEND_BUILD=true 自动编译)"

    local public_dir="$WEB_FRONTEND_DIR/.output/public"
    if [ -d "$public_dir" ]; then
        local html_file html_count=0 web_found=0
        while IFS= read -r -d '' html_file; do
            html_count=$((html_count + 1))
            if grep -q '/web/' "$html_file" 2>/dev/null; then
                web_found=1
                break
            fi
        done < <(find "$public_dir" -name '*.html' -type f -print0 2>/dev/null)
        if [ "$html_count" -gt 0 ] && [ "$web_found" -eq 0 ]; then
            die "PC Nuxt public HTML 未包含 /web/ 前缀，可能未按 NUXT_APP_BASE_URL=/web/ 构建。"
        fi
    fi
}

assert_admin_public_path() {
    local index="$1" admin_script="$2"
    if ! grep -qE '(/admin/|\./)' "$index"; then
        if grep -qE '(src|href)="/(js|css|img|fonts)/' "$index"; then
            die "管理后台 index.html 仍引用根路径静态资源，未按 /admin/ 构建。请设 PACK_FRONTEND_BUILD=true 或先执行:
  cd $ADMIN_FRONTEND_DIR && VUE_APP_PUBLIC_PATH=/admin/ VUE_APP_BASE_API=/api VUE_APP_DEFAULT_LANG=$PACK_DEFAULT_LANG npm run $admin_script"
        fi
    fi
}

assert_h5_public_path() {
    local index="$1" platform="$2"
    if grep -qE '(src|href)="/(js|css|chunk|assets)/' "$index"; then
        die "H5 index.html 仍引用根路径静态资源，未按 /mobile/ 构建。请设 PACK_FRONTEND_BUILD=true 或先执行:
  cd $MOBILE_FRONTEND_DIR && APP_PUBLIC_PATH=/mobile/ APP_ROUTER_BASENAME=/mobile APP_BASE_URL=/api/h5app/wxapp/ APP_IMAGE_CDN=$APP_IMAGE_CDN APP_PLATFORM=$platform APP_I18N_ORIGIN_LANG=$PACK_DEFAULT_LANG npm run build:h5"
    fi
}

pack_frontend() {
    local platform="$1" admin_script="$2" business_mode="$3"
    [ "$PACK_FRONTEND" = "true" ] || { log "PACK_FRONTEND=false，跳过前端打包。"; return 0; }

    if [ "$PACK_FRONTEND_BUILD" = "true" ]; then
        [ -f "$ADMIN_FRONTEND_DIR/package.json" ] || die "未找到管理后台: $ADMIN_FRONTEND_DIR"
        [ -f "$MOBILE_FRONTEND_DIR/package.json" ] || die "未找到 H5: $MOBILE_FRONTEND_DIR"
        [ -f "$WEB_FRONTEND_DIR/package.json" ] || die "未找到 PC Nuxt: $WEB_FRONTEND_DIR"
        build_admin_frontend "$admin_script"
        build_mobile_frontend "$platform"
        build_web_frontend "$business_mode"
    fi

    local admin_dist="$ADMIN_FRONTEND_DIR/dist"
    local h5_dist="$MOBILE_FRONTEND_DIR/dist/h5"
    local web_output="$WEB_FRONTEND_DIR/.output"

    [ -f "$admin_dist/index.html" ] || die "缺少管理后台产物 $admin_dist/index.html（可设 PACK_FRONTEND_BUILD=true 自动编译）"
    [ -f "$h5_dist/index.html" ] || die "缺少 H5 产物 $h5_dist/index.html（可设 PACK_FRONTEND_BUILD=true 自动编译）"

    assert_admin_public_path "$admin_dist/index.html" "$admin_script"
    assert_h5_public_path "$h5_dist/index.html" "$platform"
    assert_web_output

    log "复制管理后台 → public/admin/ ..."
    mkdir -p "$STAGE/public/admin"
    rsync -a --delete --quiet "$admin_dist/" "$STAGE/public/admin/"

    log "复制 H5 → public/mobile/ ..."
    mkdir -p "$STAGE/public/mobile"
    rsync -a --delete --quiet "$h5_dist/" "$STAGE/public/mobile/"

    log "复制 PC SSR → web/.output/ ..."
    mkdir -p "$STAGE/web"
    rsync -a --delete --quiet "$web_output/" "$STAGE/web/.output/"
    [ -f "$STAGE/web/.output/server/index.mjs" ] \
        || die "缺少 PC SSR 产物 $STAGE/web/.output/server/index.mjs"

    log "前端已打入 public/admin、public/mobile；PC SSR → /web/ (web/.output)。"
}

# ---------------------------------------------------------------------------
# 宝塔部署文件 + PRODUCT_MODEL
# ---------------------------------------------------------------------------
place_baota_files() {
    local product_model="$1"
    log "放置 auto_install.json / install.sh / nginx.rewrite / .env (PRODUCT_MODEL=${product_model})..."
    cp "$BAOTA_DIR/auto_install.json" "$STAGE/auto_install.json"
    cp "$BAOTA_DIR/install.sh"        "$STAGE/install.sh"
    chmod +x "$STAGE/install.sh"
    cp "$BAOTA_DIR/nginx.rewrite"     "$STAGE/nginx.rewrite"
    cp "$BAOTA_DIR/env.production.tpl" "$STAGE/.env"
    if grep -qE '^PRODUCT_MODEL=' "$STAGE/.env"; then
        if sed --version 2>/dev/null | grep -q GNU; then
            sed -i "s|^PRODUCT_MODEL=.*|PRODUCT_MODEL=${product_model}|" "$STAGE/.env"
        else
            sed -i '' "s|^PRODUCT_MODEL=.*|PRODUCT_MODEL=${product_model}|" "$STAGE/.env"
        fi
    else
        printf '\nPRODUCT_MODEL=%s\n' "$product_model" >> "$STAGE/.env"
    fi
}

# ---------------------------------------------------------------------------
# 打包单个模式
# ---------------------------------------------------------------------------
pack_one_mode() {
    local label="$1"
    local platform admin_script demo_file business_mode product_model
    platform="$(mode_platform "$label")"
    admin_script="$(mode_admin_script "$label")"
    demo_file="$(mode_demo_sql "$label")"
    business_mode="$(mode_business_mode "$label")"
    product_model="$(mode_product_model "$label")"

    if [ -n "${PACK_ADMIN_SCRIPT+x}" ] && [ "${#MODES[@]}" -eq 1 ]; then
        admin_script="$PACK_ADMIN_SCRIPT"
    fi

    STAGE="$TMP_BASE/ECShopX-$label"
    rm -rf "$STAGE"
    mkdir -p "$STAGE"

    log "======== 打包模式: $label (PACK_PLATFORM=$platform, PRODUCT_MODEL=$product_model) ========"
    log "暂存目录: $STAGE"

    log "复制后端源码..."
    rsync_backend

    if [ -z "$VENDOR_CACHE" ]; then
        pack_composer
    else
        copy_vendor_cache
    fi

    pack_demo_sql "$demo_file"
    pack_frontend "$platform" "$admin_script" "$business_mode"
    place_baota_files "$product_model"

    local out_zip="$OUT_DIR/ecshopx-${VERSION}-${label}-baota.zip"
    log "生成 zip: $out_zip"
    rm -f "$out_zip"
    ( cd "$TMP_BASE" && zip -rq "$out_zip" "ECShopX-$label" )
    log "完成 -> $out_zip"
}

mkdir -p "$OUT_DIR"
for label in "${MODES[@]}"; do
    pack_one_mode "$label"
done

log "上传到宝塔：软件商店 > 一键部署 > 导入项目（务必勾选创建数据库）。"
