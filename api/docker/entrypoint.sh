#!/bin/sh
set -e

# Prepara a aplicação na primeira execução e a mantém atualizada a cada subida.
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Instala as dependências na primeira vez e sempre que o composer.json mudar.
HASH=$(md5sum composer.json | cut -d' ' -f1)
if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
elif [ "$(cat vendor/.composer-hash 2>/dev/null)" != "$HASH" ]; then
    composer update --no-interaction --prefer-dist
fi
echo "$HASH" > vendor/.composer-hash

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

php artisan migrate --force

exec "$@"
