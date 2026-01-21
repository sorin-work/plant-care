#!/bin/sh
set -e

if [ "$1" = 'apache2-foreground' ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    php bin/console app:species:import
    if [ "${SEED_DEMO:-false}" = 'true' ]; then
        php bin/console app:demo:seed
    fi
    chown -R www-data:www-data var
fi

exec docker-php-entrypoint "$@"
