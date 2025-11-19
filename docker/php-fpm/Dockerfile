FROM registry.cn-hangzhou.aliyuncs.com/espier/php:7.2-fpm-alpine3.12

ARG DOCKERFILE_DIR

WORKDIR /var/www/html

COPY . /usr/src/html

COPY ${DOCKERFILE_DIR}/entrypoint.sh /usr/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]

CMD ["php-fpm"]
