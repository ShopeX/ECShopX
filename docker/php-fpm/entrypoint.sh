#!/usr/bin/env sh

set -e

role=${CONTAINER_ROLE:-web}
env=${APP_ENV:-staging}

echo "welcome..."

init_codes() {
    tar --create \
	--file - \
	--one-file-system \
	--directory /usr/src/html \
	--owner www-data --group www-data \
	. | tar --extract --file -
    chown -R www-data:www-data .
}

init_codes

if [ "$env" = "production" ]; then
    echo "Preparing ${env} environment..."
    cp .env.production .env
    chown www-data:www-data .env

elif [ "$env" = "staging" ]; then
    echo "Preparing ${env} environment..."
    cp .env.staging .env
    chown www-data:www-data .env

fi
#cd /var/www/html && php artisan config:cache && php artisan route:cache && php artisan view:cache
#cd -

apk add sudo

if [ "$role" = "web" ]; then
    echo "Web role"
    php-fpm
elif [ "$role" = "worker" ]; then
    echo "Worker role"
    apk add supervisor
    /usr/bin/supervisord -c /etc/supervisord.conf -n
elif [ "$role" = "scheduler" ]; then
    echo "Scheduler role"
    apk add supervisor
    /usr/bin/supervisord -c /etc/supervisord.conf -n
elif [ "$role" = "installer" ]; then
    echo "Installer role"
    #php /var/www/html/artisan migrate:fresh --force
    #php /var/www/html/artisan migrate:fresh
elif [ "$role" = "upgrader" ]; then
    echo "Upgrader role"
    if [ "$env" = "production" ]; then
	apk add expect
	{ \
	  echo "#!/usr/bin/expect"; \
	  echo "# Change a login shell to tcsh"; \
	  echo "spawn sudo -E -u www-data /var/www/html/artisan doctrine:migrations:migrate"; \
	  echo 'expect "> "'; \
	  echo 'send "yes\n"'; \
	  echo "expect eof"; \
	  echo "exit"; \
	  } > migrate.sh
	chmod a+x migrate.sh
	./migrate.sh
	sleep 30
    else
	sudo -E -u www-data /var/www/html/artisan doctrine:migrations:migrate
	sleep 30
    fi
elif [ "$role" = "administer" ]; then
    echo "Admister role, can execute commands"
    exec "$@"

else
    echo "don't support role: ${role}"
    echo "$@"
    exit 1
fi
