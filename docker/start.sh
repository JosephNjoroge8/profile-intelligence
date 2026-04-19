#!/bin/sh

set -e

echo "==> Clearing Laravel caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true

echo "==> Caching Laravel config and routes..."
php artisan config:cache
php artisan route:cache

echo "==> Running database migrations..."
timeout 30 php artisan migrate --force || echo "Database migrations skipped or timed out"

echo "==> Starting supervisord (nginx + php-fpm)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
