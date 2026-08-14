# ECShopX 宝塔一键部署环境配置模板
# DB 三项使用 BT_DB_* 占位符：宝塔 PHP 包仅在有 import.sql 时才会替换，
# 本项目由 install.sh 从面板 SQLite 自行注入真实库名/账号/密码。
# install.sh 还会生成 APP_KEY、JWT_SECRET、Redis 密码。

# 应用名称
APP_NAME=ECShopX
# 生产环境
APP_ENV=production
# 数据加密密钥（install.sh 自动生成）
APP_KEY=
# 生产环境关闭调试
APP_DEBUG=false
# php访问域名，不要带最后的斜杠，部署后请改为实际后台域名
APP_URL=http://127.0.0.1
# 时区不要修改
APP_TIMEZONE=PRC

# 数据库相关配置（宝塔自动注入）
DB_CONNECTION=default
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=BT_DB_NAME
DB_USERNAME=BT_DB_USERNAME
DB_PASSWORD=BT_DB_PASSWORD

# REDIS配置
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
# install.sh 自动生成密码
REDIS_PASSWORD=
REDIS_PORT=6379
REDIS_DATABASE=0
REDIS_CACHE_DATABASE=1

# JWT配置（install.sh 自动生成 JWT_SECRET）
JWT_SECRET=
JWT_TTL=7200
JWT_REFRESH_TTL=20160

# 缓存与队列驱动
CACHE_DRIVER=redis
QUEUE_DRIVER=redis

# 文件存储：默认本地存储
DISK_DRIVER=local
OSS_PROJECT_NAME=default_project

# 系统配置
SYSTEM_IS_SAAS=false
SYSTEM_MAIN_COMPANYS_ID=1
USE_SYSTEM_MENU=true
PRODUCT_MODEL=platform

# SWOOLE 配置
SERVER_HOST=0.0.0.0
SERVER_PORT=9058

# websocket配置
WEBSOCKET_SERVER_PORT=9051
WEBSOCKET_SERVER_HOST=

# 是否加密敏感数据
ENCRYPT_SENSITIVE_DATA=false
