#!/bin/sh

set -e

echo "==> Caching Laravel config and routes..."
php artisan config:cache
php artisan route:cache

echo "==> Running database migrations..."
php artisan migrate --force

echo "==> Starting supervisord (nginx + php-fpm)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
