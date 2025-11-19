#!/bin/sh

# default timezone
if [ ! -n "$TZ" ]; then
    export TZ="Asia/Shanghai"
fi

# set timezone
ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && \
echo $TZ > /etc/timezone

# php-fpm env default export
if [ ! -n "$FPM_LISTEN" ]; then
    export FPM_LISTEN="127.0.0.1:9000"
fi

if [ ! -n "$FPM_PM" ]; then
    export FPM_PM="dynamic"
fi

if [ ! -n "$FPM_PM_MAX_CHILDREN" ]; then
    export FPM_PM_MAX_CHILDREN="40"
fi

if [ ! -n "$FPM_PM_MIN_SPARE_SERVERS" ]; then
    export FPM_PM_MIN_SPARE_SERVERS="1"
fi

if [ ! -n "$FPM_PM_MAX_SPARE_SERVERS" ]; then
    export FPM_PM_MAX_SPARE_SERVERS="3"
fi

if [ ! -n "$FPM_PHP_ADMIN_VALUE_MEMORY_LIMIT" ]; then
    export FPM_PHP_ADMIN_VALUE_MEMORY_LIMIT="64M"
fi

crond -f -b -l 0 -L /data/httpd/storage/logs/supervisor-bloated-scheduler-crontab.log
supervisord -c /etc/supervisord.conf
if [ ! -z "$1" ]; then
    exec "$@"
fi

cd /data/httpd && php artisan --force doctrine:migrations:migrate