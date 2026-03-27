FROM ecshopex-php:ecx-8.2.29-fpm-alpine3.22 as builder

WORKDIR /app
COPY . /app

RUN php composer.phar config repo.packagist composer https://mirrors.aliyun.com/composer/ \
    && php composer.phar clear-cache \
    && php composer.phar install -o \
    && find . -name '\.git' | xargs rm -rf

FROM ecshopex-php:ecx-8.2.29-fpm-alpine3.22

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
    && chmod +x /usr/bin/entrypoint.sh 

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
CMD ["php-fpm","-F"]