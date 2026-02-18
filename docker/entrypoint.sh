#!/bin/bash
set -e

# Auto-generate APP_KEY if not provided
if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(php -r "echo base64_encode(random_bytes(32));")"
    export APP_KEY
    echo ">>> GENERATED APP_KEY: $APP_KEY — save this as a GitHub secret"
fi

# Auto-generate JWT_SECRET if not provided
if [ -z "$JWT_SECRET" ]; then
    JWT_SECRET=$(php artisan jwt:secret --show)
    export JWT_SECRET
    echo ">>> GENERATED JWT_SECRET: $JWT_SECRET — save this as a GitHub secret"
fi

php artisan config:cache
php artisan route:cache
php artisan migrate --force

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
