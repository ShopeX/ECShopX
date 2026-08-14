# Release app image: runtime only. Application code is bind-mounted at /data/httpd.
ARG BASE_IMAGE=registry.cn-hangzhou.aliyuncs.com/shopex_company/ecshopex-php:ecx-8.2.29-fpm-alpine3.22-openresty-node-20.19
FROM ${BASE_IMAGE}

ENV TZ=Asia/Shanghai \
    FPM_LISTEN=127.0.0.1:9000 \
    RELEASE_CFG_DIR=/opt/ecshopx-release

WORKDIR /data/httpd

RUN mkdir -p \
      /data/httpd/ECShopX \
      /data/httpd/ECShopX_admin-frontend \
      /data/httpd/ECShopX_mobile-frontend \
      /data/httpd/ECShopX_web-frontend \
      /opt/ecshopx-release \
      /var/log/supervisor \
      /var/log/nginx \
      /var/lib/nginx/tmp/client_body \
      /var/lib/nginx/tmp/proxy \
      /var/lib/nginx/tmp/fastcgi \
      /var/lib/nginx/tmp/uwsgi \
      /var/lib/nginx/tmp/scgi \
    && (id -u www-data >/dev/null 2>&1 || (addgroup -g 82 www-data 2>/dev/null || true; adduser -D -s /sbin/nologin -u 82 -G www-data www-data 2>/dev/null || true))

COPY nginx.conf /opt/ecshopx-release/nginx.conf
COPY supervisord.d/ /opt/ecshopx-release/supervisord.d/
COPY cron/ /opt/ecshopx-release/cron/
COPY entrypoint.sh /usr/bin/entrypoint.sh
RUN chmod +x /usr/bin/entrypoint.sh

# Do NOT COPY application source — volumes provide the code at runtime.

EXPOSE 8080 8081 8082

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
CMD ["supervisord"]
