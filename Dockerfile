# ── Build stage: install PHP dependencies ─────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

# Copy only dependency files first (layer cache — reinstalls only if these change)
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# ── Runtime stage ──────────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine

# System dependencies for nginx, supervisor, and PostgreSQL driver
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpq-dev \
    curl \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql opcache \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

# Copy vendor and composer from build stage
COPY --from=vendor /app/vendor ./vendor
COPY --from=vendor /usr/bin/composer /usr/bin/composer

# Copy application source
COPY . .

# Run post-install hooks (service providers, etc.)
RUN composer dump-autoload --optimize --no-dev --no-scripts

# Set storage permissions
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy docker config files
COPY docker/nginx.conf       /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh         /start.sh
RUN chmod +x /start.sh

# Fly.io expects port 8080 by default
EXPOSE 8080

CMD ["/start.sh"]
