# Stage 1: Build Vite frontend assets
FROM node:22-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci

COPY . .
RUN npm run build

# Stage 2: Production PHP 8.4 + Nginx + PHP-FPM
FROM serversideup/php:8.4-fpm-nginx

USER root
RUN install-php-extensions intl

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

USER www-data
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan storage:link || true

ENV PHP_OPCACHE_ENABLE=1

EXPOSE 8080

