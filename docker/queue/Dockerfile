FROM hub.c.163.com/bryant123/service-php7_swoole:0.0.4

WORKDIR /srv/www/espier-bloated
COPY . /srv/www/espier-bloated

RUN chown -R www:www . \
    && cp /srv/www/espier-bloated/etc/supervisord.conf /etc/supervisord.conf \
    && cp -rf /srv/www/espier-bloated/etc/queue/supervisord.d/* /etc/supervisord.d/

ADD ./docker/queue/cron.d/root /etc/cron.d/root

#CMD ["supervisord", "-c", "/srv/www/espier-bloated/etc/supervisord.conf"]
ENTRYPOINT chown -R www:www /srv/www/espier-bloated/storage/logs && /usr/bin/supervisord -c /srv/www/espier-bloated/etc/supervisord.conf

# websocket
EXPOSE 9051
# 预留
EXPOSE 9058
