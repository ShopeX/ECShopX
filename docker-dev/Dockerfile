FROM registry.cn-hangzhou.aliyuncs.com/shopex_company/ecshopex-php:ecx-8.2.29-fpm-alpine3.22

ARG NODE_VERSION=16.20.2
ARG NODE_DIST_BASE=https://nodejs.org/dist
ARG NODE_MUSL_DIST_BASE=https://unofficial-builds.nodejs.org/download/release

# 安装 MySQL、Redis、Nginx、Supervisor 和 Node.js
# 基础镜像已包含 PHP 8.2 和 FPM，只需安装其他服务
RUN apk add --no-cache \
    # MySQL (MariaDB)
    mariadb \
    mariadb-client \
    # Redis
    redis \
    # Nginx
    nginx \
    # Supervisor
    supervisor \
    # 其他工具
    bash \
    curl \
    tzdata \
    ca-certificates \
    python3 \
    make \
    g++ \
    xz \
    libstdc++ \
    libc6-compat \
    && ARCH="$(uname -m)" \
    && case "$ARCH" in \
         x86_64) NODE_ARCH=x64 ;; \
         aarch64) NODE_ARCH=arm64 ;; \
         *) echo "unsupported architecture: $ARCH (need x86_64 or aarch64)" >&2; exit 1 ;; \
       esac \
    && if [ "$NODE_ARCH" = "x64" ]; then \
         NODE_TARBALL_URL="${NODE_MUSL_DIST_BASE}/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-x64-musl.tar.xz"; \
       else \
         NODE_TARBALL_URL="${NODE_DIST_BASE}/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-${NODE_ARCH}.tar.xz"; \
       fi \
    && echo "Installing Node from ${NODE_TARBALL_URL}" \
    && curl -fsSL "${NODE_TARBALL_URL}" \
      | tar -xJ -C /usr/local --strip-components=1 \
    && rm -rf /var/cache/apk/* \
    && npm config set registry https://registry.npmmirror.com \
    && node --version && npm --version

# 设置时区
ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 创建必要的目录和用户
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /etc/supervisor/conf.d \
    && mkdir -p /var/lib/mysql \
    && mkdir -p /var/run/mysqld \
    && mkdir -p /var/lib/redis \
    && mkdir -p /var/log/redis \
    && mkdir -p /var/run/redis \
    && mkdir -p /var/log/nginx \
    && mkdir -p /var/log/php-fpm \
    && mkdir -p /run/php \
    && mkdir -p /var/lib/nginx/tmp/client_body \
    && mkdir -p /var/lib/nginx/tmp/proxy \
    && mkdir -p /var/lib/nginx/tmp/fastcgi \
    && mkdir -p /var/lib/nginx/tmp/uwsgi \
    && mkdir -p /var/lib/nginx/tmp/scgi \
    && mkdir -p /data/httpd/ECShopX \
    && mkdir -p /data/httpd/ECShopX_admin-frontend \
    && mkdir -p /data/httpd/ECShopX_mobile-frontend \
    && mkdir -p /data/httpd/ECShopX_desktop-frontend \
    && (id -u redis >/dev/null 2>&1 || adduser -D -s /sbin/nologin redis) \
    && (id -u www-data >/dev/null 2>&1 || (addgroup -g 82 www-data && adduser -D -s /sbin/nologin -u 82 -G www-data www-data)) \
    && chown -R redis:redis /var/lib/redis /var/log/redis /var/run/redis \
    && chown -R www-data:www-data /var/log/php-fpm /run/php /var/lib/nginx /var/log/nginx

# 只复制配置文件（项目代码通过 volumes 挂载）
COPY docker-dev/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker-dev/docker-entrypoint.sh /usr/bin/entrypoint.sh
COPY docker-dev/nginx.conf /etc/nginx/nginx.conf
COPY docker-dev/php-fpm.conf /tmp/php-fpm-custom.conf
COPY docker-dev/mysql.cnf /etc/my.cnf

# 创建 MySQL 配置链接
RUN mkdir -p /etc/mysql && \
    (ln -sf /etc/my.cnf /etc/mysql/my.cnf 2>/dev/null || true)

# 设置权限
RUN chmod +x /usr/bin/entrypoint.sh \
    && chown -R mysql:mysql /var/lib/mysql \
    && chown -R mysql:mysql /var/run/mysqld

# 工作目录
WORKDIR /data/httpd

# 暴露端口
EXPOSE 80 3306 6379

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf", "-n"]
