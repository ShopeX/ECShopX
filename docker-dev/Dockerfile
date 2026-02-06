FROM registry.cn-hangzhou.aliyuncs.com/shopex_company/ecshopex-php:ecx-7.4.32-fpm-alpine3.16

# 安装 MySQL、Redis、Nginx、Supervisor 和 Node.js
# 基础镜像已包含 PHP 7.4.32 和 FPM，只需安装其他服务
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
    # Node.js 16.x（从 Alpine 官方源安装）
    nodejs=16.20.2-r0 \
    npm \
    # 其他工具
    bash \
    curl \
    tzdata \
    ca-certificates \
    # Node.js 编译依赖
    python3 \
    make \
    g++ \
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
