#!/bin/sh
set -e

# A hospedagem informa a porta pela variável PORT.
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -z "$APP_KEY" ]; then
    echo "Defina a variável APP_KEY com um valor secreto." >&2
    exit 1
fi

if [ -z "$DB_URL" ] && [ -z "$DB_HOST" ]; then
    echo "Defina a variável DB_URL com o endereço do PostgreSQL." >&2
    exit 1
fi

# O Laravel exige uma chave de 32 bytes no formato "base64:...". Qualquer outro
# segredo é convertido para esse formato, sempre com o mesmo resultado.
case "$APP_KEY" in
    base64:*) ;;
    *) APP_KEY="base64:$(php -r 'echo base64_encode(hash("sha256", getenv("APP_KEY"), true));')"
       export APP_KEY ;;
esac

# Guarda a configuração e as rotas já lidas, para não reprocessar a cada requisição.
php artisan config:cache
php artisan route:cache
php artisan migrate --force

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
