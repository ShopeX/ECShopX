FROM registry.cn-hangzhou.aliyuncs.com/shopex_company/ecshopex-php:ecx-7.4.32-fpm-alpine3.16 as builder

WORKDIR /app
COPY . /app

RUN php composer.phar config repo.packagist composer https://mirrors.aliyun.com/composer/ \
    && php composer.phar clear-cache \
    && php composer.phar install -o \
    && find . -name '\.git' | xargs rm -rf

FROM registry.cn-hangzhou.aliyuncs.com/shopex_company/ecshopex-php:ecx-7.4.32-fpm-alpine3.16

WORKDIR /data/httpd
COPY ./docker-new/entrypoint.sh /usr/bin/entrypoint.sh
COPY  --from=builder /app .

RUN set -eux \
    && apk --no-cache add ca-certificates tzdata \
           --repository https://mirrors.aliyun.com/alpine/v3.9/main/ \
#  Install supervisor
    && apk add supervisor \
    && mkdir /etc/supervisor.d/ \
# Clean files
    && cd /data/httpd/ \
    && rm -f .env.example .env.production .env.staging .env.star \
# Set directory permissions
    && mkdir -p ./storage/logs \
    && mkdir -p ./storage/framework/cache/laravel-excel \
    && chown www-data:www-data -R ./storage \
    && chmod +x /usr/bin/entrypoint.sh \
# add swoole_license config
    && echo "swoole_license_files=/data/httpd/license.zl" >> /usr/local/etc/php/conf.d/docker-php-ext-swoole_loader74.ini

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
CMD ["php-fpm","-F"]