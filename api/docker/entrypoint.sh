#!/bin/sh
set -e

# Prepara a aplicação na primeira execução e aplica as migrations a cada subida.
if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

php artisan migrate --force

exec "$@"
