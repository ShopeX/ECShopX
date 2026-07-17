#!/bin/sh
# ECShopX Web 前端（Nuxt）容器启动脚本
# 容器重启后自动启动已编译的 Nuxt 生产服务

APP_DIR="/data/httpd/ECShopX_web-frontend"
BUILD_FILE="$APP_DIR/.output/server/index.mjs"
LOG_FILE="/var/log/nuxt.log"
WAIT_INTERVAL="${NUXT_WAIT_INTERVAL:-10}"

export TZ="${TZ:-Asia/Shanghai}"
export NITRO_HOST=0.0.0.0
export NITRO_PORT=3000
export HOST=0.0.0.0
export PORT=3000

cd "$APP_DIR" || {
    echo "错误: 无法进入目录 $APP_DIR"
    exit 1
}

echo "等待 Nuxt 生产构建产物: $BUILD_FILE"
while [ ! -f "$BUILD_FILE" ]; do
    echo "未找到构建产物，${WAIT_INTERVAL} 秒后重试（可先运行 dev-setup.sh 完成编译）..."
    sleep "$WAIT_INTERVAL"
done

echo "启动 Nuxt 服务 (0.0.0.0:3000)..."
exec node .output/server/index.mjs >> "$LOG_FILE" 2>&1
