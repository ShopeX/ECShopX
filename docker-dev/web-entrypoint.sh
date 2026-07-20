#!/bin/sh
# ECShopX Web 前端（Nuxt）容器启动脚本
# 容器重启后自动启动已编译的 Nuxt 生产服务

APP_DIR="/data/httpd/ECShopX_web-frontend"
BUILD_FILE="$APP_DIR/.output/server/index.mjs"
LOG_FILE="/var/log/nuxt.log"
WAIT_INTERVAL="${NUXT_WAIT_INTERVAL:-10}"
PUBLIC_API_BASE="${NUXT_PUBLIC_API_BASE:-http://localhost:8080/api/h5app}"
INTERNAL_API_BASE="${NUXT_INTERNAL_API_BASE:-${NUXT_API_BASE:-http://ecshopx-dev:8080/api/h5app}}"

export TZ="${TZ:-Asia/Shanghai}"
export NITRO_HOST=0.0.0.0
export NITRO_PORT=3000
export HOST=0.0.0.0
export PORT=3000

cd "$APP_DIR" || {
    echo "错误: 无法进入目录 $APP_DIR"
    exit 1
}

start_local_api_proxy() {
    node <<'NODE_PROXY' &
const http = require('http')
const https = require('https')

const publicBase = process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8080/api/h5app'
const internalBase =
  process.env.NUXT_INTERNAL_API_BASE ||
  process.env.NUXT_API_BASE ||
  'http://ecshopx-dev:8080/api/h5app'

let publicUrl
let internalUrl
try {
  publicUrl = new URL(publicBase)
  internalUrl = new URL(internalBase)
} catch (error) {
  console.error(`[SSR API Proxy] API URL 解析失败: ${error.message}`)
  process.exit(0)
}

const localHosts = new Set(['localhost', '127.0.0.1', '::1'])
if (!localHosts.has(publicUrl.hostname)) {
  console.log(`[SSR API Proxy] NUXT_PUBLIC_API_BASE=${publicBase} 不是本机地址，跳过本地转发`)
  process.exit(0)
}

const listenHost = publicUrl.hostname === '::1' ? '::1' : '127.0.0.1'
const listenPort = Number(publicUrl.port || (publicUrl.protocol === 'https:' ? 443 : 80))
const publicPath = publicUrl.pathname.replace(/\/+$/, '') || '/'
const internalPath = internalUrl.pathname.replace(/\/+$/, '') || '/'
const transport = internalUrl.protocol === 'https:' ? https : http

function joinTargetPath(requestPath) {
  const suffix = requestPath.startsWith(publicPath) ? requestPath.slice(publicPath.length) : requestPath
  const normalizedSuffix = suffix.startsWith('/') ? suffix : `/${suffix}`
  return `${internalPath}${normalizedSuffix}`.replace(/\/{2,}/g, '/')
}

const server = http.createServer((req, res) => {
  const incomingUrl = new URL(req.url || '/', publicUrl)
  const headers = { ...req.headers, host: internalUrl.host }
  const targetPath = `${joinTargetPath(incomingUrl.pathname)}${incomingUrl.search}`
  const proxyReq = transport.request(
    {
      protocol: internalUrl.protocol,
      hostname: internalUrl.hostname,
      port: internalUrl.port || (internalUrl.protocol === 'https:' ? 443 : 80),
      method: req.method,
      path: targetPath,
      headers,
    },
    (proxyRes) => {
      res.writeHead(proxyRes.statusCode || 502, proxyRes.headers)
      proxyRes.pipe(res)
    }
  )

  proxyReq.on('error', (error) => {
    console.error(`[SSR API Proxy] ${req.method} ${targetPath} -> ${error.message}`)
    if (!res.headersSent) {
      res.writeHead(502, { 'content-type': 'text/plain; charset=utf-8' })
    }
    res.end('Bad Gateway')
  })

  req.pipe(proxyReq)
})

server.on('error', (error) => {
  if (error.code === 'EADDRINUSE') {
    console.log(`[SSR API Proxy] ${listenHost}:${listenPort} 已被占用，跳过本地转发`)
    return
  }
  console.error(`[SSR API Proxy] 启动失败: ${error.message}`)
})

server.listen(listenPort, listenHost, () => {
  console.log(`[SSR API Proxy] http://${listenHost}:${listenPort}${publicPath} -> ${internalBase}`)
})
NODE_PROXY
}

export NUXT_PUBLIC_API_BASE="$PUBLIC_API_BASE"
export NUXT_INTERNAL_API_BASE="$INTERNAL_API_BASE"
start_local_api_proxy

echo "等待 Nuxt 生产构建产物: $BUILD_FILE"
while [ ! -f "$BUILD_FILE" ]; do
    echo "未找到构建产物，${WAIT_INTERVAL} 秒后重试（可先运行 dev-setup.sh 完成编译）..."
    sleep "$WAIT_INTERVAL"
done

echo "启动 Nuxt 服务 (0.0.0.0:3000)..."
exec node .output/server/index.mjs >> "$LOG_FILE" 2>&1
