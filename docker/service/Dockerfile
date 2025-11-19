FROM hub.c.163.com/bryant123/service-php7_swoole:0.0.4

WORKDIR /srv/www/espier-bloated
COPY . /srv/www/espier-bloated
RUN chown -R www:www . \
    && cp /srv/www/espier-bloated/etc/supervisord.conf /etc/supervisord.conf \
    && cp -rf /srv/www/espier-bloated/etc/service/supervisord.d/* /etc/supervisord.d/

#CMD ["supervisord", "-c", "/srv/www/espier-bloated/etc/supervisord.conf"]
ENTRYPOINT chown -R www:www /srv/www/espier-bloated/storage/logs && /usr/bin/supervisord -c /srv/www/espier-bloated/etc/supervisord.conf

EXPOSE 9058
