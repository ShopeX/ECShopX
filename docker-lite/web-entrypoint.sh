#!/bin/sh
# Nuxt production server for release compose (service name: web).

APP_DIR="/data/httpd/ECShopX_web-frontend"
BUILD_FILE="$APP_DIR/.output/server/index.mjs"
WAIT_INTERVAL="${NUXT_WAIT_INTERVAL:-10}"
PUBLIC_API_BASE="${NUXT_PUBLIC_API_BASE:-http://localhost:8080/api/h5app}"
INTERNAL_API_BASE="${NUXT_INTERNAL_API_BASE:-${NUXT_API_BASE:-http://127.0.0.1:8090/api/h5app}}"

export TZ="${TZ:-Asia/Shanghai}"
export NITRO_HOST=0.0.0.0
export NITRO_PORT=3000
export HOST=0.0.0.0
export PORT=3000
export NUXT_PUBLIC_API_BASE="$PUBLIC_API_BASE"
export NUXT_INTERNAL_API_BASE="$INTERNAL_API_BASE"

cd "$APP_DIR" || {
    echo "错误: 无法进入目录 $APP_DIR"
    exit 1
}

echo "等待 Nuxt 生产构建产物: $BUILD_FILE"
while [ ! -f "$BUILD_FILE" ]; do
    echo "未找到构建产物，${WAIT_INTERVAL} 秒后重试..."
    sleep "$WAIT_INTERVAL"
done

echo "启动 Nuxt 服务 (0.0.0.0:3000)"
node -v
exec node .output/server/index.mjs
