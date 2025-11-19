FROM registry.cn-hangzhou.aliyuncs.com/shopex_company/ecshopex-php:7.4-fpm-alpine3.12 as builder

RUN apk --no-cache add ca-certificates tzdata git openssh-client
WORKDIR /app
COPY . /app
COPY ./docker-free/docker-php-ext-opcache.ini /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

#ARG SSH_PRIVATE_KEY
ARG EGO_GIT_REPO

    # mkdir /root/.ssh/ \
    # && echo "${SSH_PRIVATE_KEY}" > /root/.ssh/id_rsa \
    # && chmod 0600 /root/.ssh/id_rsa \
    # && touch /root/.ssh/known_hosts \
    # && ssh-keyscan git.ishopex.cn >> /root/.ssh/known_hosts \
    # && chmod 0600 /root/.ssh/known_hosts \
RUN cd /app \
    && php composer.phar config repo.packagist composer https://mirrors.aliyun.com/composer/ \
    && php composer.phar clear-cache \
    && php composer.phar install -o \
    && find . -name '\.git' | xargs rm -rf

RUN git clone ${EGO_GIT_REPO}

FROM registry.cn-hangzhou.aliyuncs.com/shopex_company/ecshopex-php:7.4-fpm-alpine3.12

COPY ./docker-free/docker-php-ext-opcache.ini /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
COPY ./docker-free/entrypoint.sh /usr/bin/entrypoint.sh

WORKDIR /data/httpd
COPY  --from=builder /app .

RUN set -eux \
    && apk --no-cache add ca-certificates tzdata sudo supervisor \
    && mkdir /etc/supervisor.d \
    && cp -f /data/httpd/docker-free/cron/www-data /etc/crontabs/root \
    && cp -f /data/httpd/docker-free/supervisord.conf /etc/supervisord.conf \
    && cp -f /data/httpd/docker-free/supervisor/* /etc/supervisor.d/ \
# Replace Ego
    && rm -rf /data/httpd/src/CompanysBundle/Ego \
    && cp -r /data/httpd/free-for-atlas/Ego /data/httpd/src/CompanysBundle/ \
    && cp -f /data/httpd/free-for-atlas/license.zl /data/httpd/license.zl \
# Clean files
    && rm -f .env.example .env.production .env.staging .env.star \
    && rm -rf free-for-atlas \
# Set directory permissions
    && mkdir -p ./storage/logs \
    && mkdir -p ./storage/framework/cache/laravel-excel \
    && chown -R www-data:www-data ./storage \
    && chmod +x /usr/bin/entrypoint.sh \
# add swoole_license config
    && echo "swoole_license_files=/data/httpd/license.zl" >> /usr/local/etc/php/conf.d/docker-php-ext-swoole_loader74.ini

ENTRYPOINT ["/usr/bin/entrypoint.sh"]

CMD ["php-fpm","-F"]
