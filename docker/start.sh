#!/bin/sh

set -e

echo "==> Caching Laravel config and routes..."
php artisan config:cache
php artisan route:cache

echo "==> Running database migrations..."
timeout 30 php artisan migrate --force || echo "Database migrations skipped or timed out"

echo "==> Starting supervisord (nginx + php-fpm)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
