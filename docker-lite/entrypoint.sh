#!/bin/sh
# Release app entrypoint: install nginx/cron/supervisor configs, then supervisord.
# Application code is mounted at /data/httpd (not baked into the image).

set -eu

if [ -z "${TZ:-}" ]; then
    TZ="Asia/Shanghai"
    export TZ
fi
ln -snf "/usr/share/zoneinfo/$TZ" /etc/localtime 2>/dev/null || true
echo "$TZ" > /etc/timezone 2>/dev/null || true

export FPM_LISTEN="${FPM_LISTEN:-127.0.0.1:9000}"

RELEASE_CFG="${RELEASE_CFG_DIR:-/opt/ecshopx-release}"
# Prefer bind-mounted release config so nginx updates apply without rebuilding the image.
MOUNTED_NGINX="/data/httpd/ECShopX/docker-lite/nginx.conf"
if [ -f "$MOUNTED_NGINX" ]; then
    NGINX_SRC="$MOUNTED_NGINX"
elif [ -f "$RELEASE_CFG/nginx.conf" ]; then
    NGINX_SRC="$RELEASE_CFG/nginx.conf"
else
    NGINX_SRC=""
fi

mkdir -p /var/log/supervisor /var/log/nginx /var/lib/nginx/tmp/client_body \
  /var/lib/nginx/tmp/proxy /var/lib/nginx/tmp/fastcgi \
  /var/lib/nginx/tmp/uwsgi /var/lib/nginx/tmp/scgi \
  /data/httpd/ECShopX/storage/logs \
  /etc/supervisord.d /etc/crontabs

# OpenResty nginx.conf
if [ -n "$NGINX_SRC" ]; then
    if [ -d /usr/local/openresty/nginx/conf ]; then
        cp "$NGINX_SRC" /usr/local/openresty/nginx/conf/nginx.conf
        echo "✓ installed OpenResty nginx.conf from $NGINX_SRC"
    elif [ -d /etc/nginx ]; then
        cp "$NGINX_SRC" /etc/nginx/nginx.conf
        echo "✓ installed /etc/nginx/nginx.conf from $NGINX_SRC"
    fi
fi

# Force FPM listen on loopback (override zz-docker.conf listen=9000)
if [ -d /usr/local/etc/php-fpm.d ]; then
    cat >/usr/local/etc/php-fpm.d/zzz-release-listen.conf <<EOF
[www]
listen = ${FPM_LISTEN}
EOF
    echo "✓ php-fpm listen=${FPM_LISTEN}"
fi

# Replace base-image run.ini with release programs + queues
rm -f /etc/supervisord.d/run.ini
if [ -d "$RELEASE_CFG/supervisord.d" ]; then
    cp -a "$RELEASE_CFG/supervisord.d/." /etc/supervisord.d/
    echo "✓ installed supervisord.d programs"
fi

# Crontab: keep alpine periodic jobs, ensure schedule:run exists
CRON_LINE='* * * * * su -s /bin/sh www-data -c "php /data/httpd/ECShopX/artisan schedule:run" >> /dev/null 2>&1'
if [ -f /etc/crontabs/root ]; then
    grep -q 'artisan schedule:run' /etc/crontabs/root 2>/dev/null || echo "$CRON_LINE" >> /etc/crontabs/root
else
    echo "$CRON_LINE" > /etc/crontabs/root
fi
chmod 600 /etc/crontabs/root
echo "✓ crontab schedule:run ready"

chown -R www-data:www-data /var/lib/nginx /var/log/nginx 2>/dev/null || true
chown -R www-data:www-data /data/httpd/ECShopX/storage 2>/dev/null || true

# SSR may call NUXT_PUBLIC_API_BASE; map that host to loopback to avoid hairpin NAT hangs.
if [ -n "${NUXT_PUBLIC_API_BASE:-}" ]; then
    pub_host=$(printf '%s' "$NUXT_PUBLIC_API_BASE" | sed -E 's|^[a-zA-Z]+://([^/:]+).*|\1|')
    case "$pub_host" in
      ''|localhost|127.0.0.1|::1) ;;
      *)
        if ! grep -qE "[[:space:]]$pub_host([[:space:]]|$)" /etc/hosts 2>/dev/null; then
          echo "127.0.0.1 $pub_host" >> /etc/hosts
          echo "✓ /etc/hosts: 127.0.0.1 $pub_host (for Nuxt SSR)"
        fi
        ;;
    esac
fi

if [ "$#" -eq 0 ] || [ "${1:-}" = "supervisord" ] || [ "${1:-}" = "php-fpm" ]; then
    # Base image uses ochinchina/supervisord (Go): foreground by default; -d = daemon.
    exec /usr/bin/supervisord -c /etc/supervisord.conf
fi

exec "$@"
