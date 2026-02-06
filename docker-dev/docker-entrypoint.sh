#!/bin/sh
# ECShopX API Docker 容器启动脚本
# 负责初始化服务、配置环境和启动 Supervisor
# 注意：某些非关键步骤允许失败（使用 || true），关键步骤失败会退出

# 设置时区
if [ -z "$TZ" ]; then
    export TZ="Asia/Shanghai"
fi
if ! ln -snf /usr/share/zoneinfo/$TZ /etc/localtime || ! echo "$TZ" > /etc/timezone; then
    echo "警告: 无法设置时区，使用默认值"
fi

# 配置 PHP-FPM（根据基础镜像的实际路径）
PHP_FPM_CONF_DIR=""
if [ -d "/usr/local/etc/php-fpm.d" ]; then
    PHP_FPM_CONF_DIR="/usr/local/etc/php-fpm.d"
elif [ -d "/etc/php7/php-fpm.d" ]; then
    PHP_FPM_CONF_DIR="/etc/php7/php-fpm.d"
elif [ -d "/etc/php-fpm.d" ]; then
    PHP_FPM_CONF_DIR="/etc/php-fpm.d"
fi

if [ -n "$PHP_FPM_CONF_DIR" ] && [ -f "/tmp/php-fpm-custom.conf" ]; then
    if cp /tmp/php-fpm-custom.conf "$PHP_FPM_CONF_DIR/www.conf"; then
        echo "✓ PHP-FPM 配置已复制到: $PHP_FPM_CONF_DIR/www.conf"
    else
        echo "✗ 错误: 无法复制 PHP-FPM 配置文件"
        exit 1
    fi
fi

# 检测 PHP-FPM 可执行文件路径（用于 supervisor 配置）
PHP_FPM_BIN=""
if [ -f "/usr/local/sbin/php-fpm" ]; then
    PHP_FPM_BIN="/usr/local/sbin/php-fpm"
elif [ -f "/usr/sbin/php-fpm7" ]; then
    PHP_FPM_BIN="/usr/sbin/php-fpm7"
elif [ -f "/usr/sbin/php-fpm" ]; then
    PHP_FPM_BIN="/usr/sbin/php-fpm"
elif command -v php-fpm &> /dev/null; then
    PHP_FPM_BIN=$(command -v php-fpm)
fi

if [ -n "$PHP_FPM_BIN" ]; then
    # 更新 supervisor 配置中的 PHP-FPM 路径
    SUPERVISOR_CONF="/etc/supervisor/conf.d/supervisord.conf"
    if [ -f "$SUPERVISOR_CONF" ]; then
        # 使用 sed 更新配置（Alpine Linux 兼容语法）
        if sed -i "s|^command=.*php-fpm.*|command=$PHP_FPM_BIN -F|" "$SUPERVISOR_CONF"; then
            echo "✓ PHP-FPM 路径已设置为: $PHP_FPM_BIN -F"
        else
            echo "警告: 无法更新 supervisord.conf 中的 PHP-FPM 路径，使用默认配置"
        fi
    else
        echo "✗ 错误: $SUPERVISOR_CONF 文件不存在"
        echo "检查文件位置..."
        find /etc -name "supervisord.conf" 2>/dev/null || echo "未找到 supervisord.conf"
        exit 1
    fi
else
    echo "警告: 未找到 PHP-FPM 可执行文件，supervisor 配置将使用默认路径"
fi

# 设置环境变量默认值（使用更简洁的语法）
export FPM_LISTEN="${FPM_LISTEN:-127.0.0.1:9000}"
export FPM_PM="${FPM_PM:-dynamic}"
export FPM_PM_MAX_CHILDREN="${FPM_PM_MAX_CHILDREN:-40}"
export FPM_PM_MIN_SPARE_SERVERS="${FPM_PM_MIN_SPARE_SERVERS:-1}"
export FPM_PM_MAX_SPARE_SERVERS="${FPM_PM_MAX_SPARE_SERVERS:-3}"
export FPM_PHP_ADMIN_VALUE_MEMORY_LIMIT="${FPM_PHP_ADMIN_VALUE_MEMORY_LIMIT:-64M}"

export MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-rootpassword}"
export MYSQL_DATABASE="${MYSQL_DATABASE:-ecshopx}"
export MYSQL_USER="${MYSQL_USER:-ecshopx}"
export MYSQL_PASSWORD="${MYSQL_PASSWORD:-ecshopx}"

export REDIS_PASSWORD="${REDIS_PASSWORD:-redispassword}"

# 初始化 MySQL（如果数据目录为空或没有系统表）
if [ ! -d "/var/lib/mysql/mysql" ] && [ ! -d "/var/lib/mysql/mariadb" ]; then
    echo "=========================================="
    echo "初始化 MySQL 数据目录..."
    echo "=========================================="
    
    if ! mysql_install_db --user=mysql --datadir=/var/lib/mysql --skip-test-db --auth-root-authentication-method=normal >/dev/null 2>&1; then
        echo "✗ 错误: MySQL 数据目录初始化失败"
        exit 1
    fi
    
    # 启动 MySQL 以创建数据库和用户
    echo "启动临时 MySQL 进程..."
    mysqld_safe --defaults-file=/etc/my.cnf --user=mysql --datadir=/var/lib/mysql --skip-networking --socket=/var/run/mysqld/mysqld.sock >/dev/null 2>&1 &
    MYSQL_PID=$!
    
    # 等待 MySQL 启动（最多等待 30 秒）
    echo "等待 MySQL 启动..."
    MYSQL_READY=false
    for i in $(seq 1 30); do
        if mysqladmin ping --socket=/var/run/mysqld/mysqld.sock --silent 2>/dev/null; then
            MYSQL_READY=true
            break
        fi
        sleep 1
    done
    
    if [ "$MYSQL_READY" = false ]; then
        echo "✗ 错误: MySQL 启动超时"
        kill $MYSQL_PID 2>/dev/null || true
        exit 1
    fi
    
    echo "✓ MySQL 已启动，创建数据库和用户..."
    # 创建数据库和用户
    if ! mysql --socket=/var/run/mysqld/mysqld.sock -uroot <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD}';
CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE};
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'localhost' IDENTIFIED BY '${MYSQL_PASSWORD}';
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'localhost';
GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
EOF
    then
        echo "✗ 错误: 无法创建数据库和用户"
        kill $MYSQL_PID 2>/dev/null || true
        exit 1
    fi
    
    # 停止 MySQL（supervisor 会重新启动）
    echo "停止临时 MySQL 进程..."
    mysqladmin --socket=/var/run/mysqld/mysqld.sock -uroot -p${MYSQL_ROOT_PASSWORD} shutdown 2>/dev/null || kill $MYSQL_PID 2>/dev/null || true
    wait $MYSQL_PID 2>/dev/null || true
    sleep 2
    echo "✓ MySQL 初始化完成"
fi

# 配置 Redis
# 确保 Redis 用户和目录存在（Dockerfile 中已创建，这里只是确保）
if ! id -u redis >/dev/null 2>&1; then
    echo "警告: Redis 用户不存在，尝试创建..."
    adduser -D -s /sbin/nologin redis || {
        echo "✗ 错误: 无法创建 Redis 用户"
        exit 1
    }
fi
mkdir -p /var/lib/redis /var/log/redis /var/run/redis
chown -R redis:redis /var/lib/redis /var/log/redis /var/run/redis || {
    echo "✗ 错误: 无法设置 Redis 目录权限"
    exit 1
}

# 确保 Nginx 临时目录存在且有正确权限
mkdir -p /var/lib/nginx/tmp/client_body \
         /var/lib/nginx/tmp/proxy \
         /var/lib/nginx/tmp/fastcgi \
         /var/lib/nginx/tmp/uwsgi \
         /var/lib/nginx/tmp/scgi \
         /var/log/nginx
chown -R www-data:www-data /var/lib/nginx /var/log/nginx || {
    echo "警告: 无法设置 Nginx 目录权限，可能影响文件上传"
}

# 配置 Redis 密码
if [ -n "$REDIS_PASSWORD" ]; then
    # 检查 Redis 配置文件是否存在
    if [ -f "/etc/redis.conf" ]; then
        # 如果已有 requirepass 配置，替换它；否则添加
        if grep -q "^requirepass" /etc/redis.conf 2>/dev/null; then
            sed -i "s/^requirepass.*/requirepass ${REDIS_PASSWORD}/" /etc/redis.conf
        else
            echo "requirepass ${REDIS_PASSWORD}" >> /etc/redis.conf
        fi
    else
        # 如果配置文件不存在，创建基本配置
        cat > /etc/redis.conf <<EOF
bind 0.0.0.0
port 6379
requirepass ${REDIS_PASSWORD}
dir /var/lib/redis
logfile /var/log/redis/redis.log
daemonize no
EOF
    fi
fi

# 处理许可证文件
if [ -n "$LICENSEZL" ]; then
    if echo "$LICENSEZL" | base64 -d > /data/httpd/ECShopX/license.zl 2>/dev/null; then
        echo "✓ 许可证文件已写入"
    else
        echo "警告: 许可证文件解码失败"
    fi
fi

# 如果提供了命令参数，直接执行（用于 docker exec 等场景）
if [ -n "$1" ]; then
    exec "$@"
fi

# 先启动 supervisord 在后台（临时修改配置为 daemon 模式）
echo "=========================================="
echo "启动 Supervisor 管理所有服务..."
echo "=========================================="

# 创建临时配置文件（daemon 模式）
TEMP_SUPERVISOR_CONF="/tmp/supervisord-daemon.conf"
cp /etc/supervisor/conf.d/supervisord.conf "$TEMP_SUPERVISOR_CONF"
# 修改为 daemon 模式
sed -i 's/nodaemon=true/nodaemon=false/' "$TEMP_SUPERVISOR_CONF" 2>/dev/null || \
    sed -i 's/nodaemon = true/nodaemon = false/' "$TEMP_SUPERVISOR_CONF" 2>/dev/null || true

# 启动 supervisord 在后台
echo "启动后台 supervisord..."
if ! /usr/bin/supervisord -c "$TEMP_SUPERVISOR_CONF"; then
    echo "✗ 错误: 无法启动 supervisord"
    exit 1
fi
echo "✓ 后台 supervisord 已启动"

# 等待服务启动
echo "等待服务启动..."
sleep 5

# 检查 supervisord 是否运行（使用临时配置文件启动的进程）
# 等待一下让 supervisord 完全启动
sleep 2
if ! supervisorctl -c "$TEMP_SUPERVISOR_CONF" status >/dev/null 2>&1; then
    echo "警告: 无法连接到后台 supervisord，尝试继续执行..."
    # 不退出，继续执行（composer 和迁移由 dev-setup.sh 处理）
fi

# 等待 MySQL 和 Redis 就绪
echo "等待 MySQL 和 Redis 服务就绪..."
MYSQL_READY=false
for i in $(seq 1 60); do
    if mysqladmin ping --socket=/var/run/mysqld/mysqld.sock --silent 2>/dev/null || \
       mysqladmin ping -h 127.0.0.1 -u root -p${MYSQL_ROOT_PASSWORD} --silent 2>/dev/null; then
        MYSQL_READY=true
        echo "✓ MySQL 已就绪"
        break
    fi
    if [ $((i % 5)) -eq 0 ]; then
        echo "  等待 MySQL... ($i/60)"
    fi
    sleep 1
done

if [ "$MYSQL_READY" = false ]; then
    echo "警告: MySQL 启动超时，继续执行..."
fi

REDIS_READY=false
for i in $(seq 1 30); do
    if redis-cli -a ${REDIS_PASSWORD} ping 2>/dev/null | grep -q "PONG"; then
        REDIS_READY=true
        echo "✓ Redis 已就绪"
        break
    fi
    if [ $((i % 5)) -eq 0 ]; then
        echo "  等待 Redis... ($i/30)"
    fi
    sleep 1
done

if [ "$REDIS_READY" = false ]; then
    echo "警告: Redis 启动超时，继续执行..."
fi

# 注意：Composer 安装和数据库迁移已移到 dev-setup.sh 脚本中执行
# 这样可以更好地控制执行时机和错误处理

# 停止后台 supervisord，然后在前台启动（成为 PID 1）
echo "=========================================="
echo "切换到前台模式运行 Supervisor..."
echo "=========================================="
supervisorctl -c "$TEMP_SUPERVISOR_CONF" shutdown 2>/dev/null || pkill supervisord 2>/dev/null || true
sleep 2

# 使用原始配置文件在前台启动（成为 PID 1）
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf -n
