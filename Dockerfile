# Stage 1: Base PHP 8.4 + Nginx + Composer dependencies
FROM serversideup/php:8.4-fpm-nginx AS base

USER root
RUN install-php-extensions intl

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .

USER www-data
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 2: Build Vite frontend assets (needs vendor/tightenco/ziggy from Stage 1)
FROM node:22-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci

COPY . .
COPY --from=base /var/www/html/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
RUN npm run build

# Stage 3: Final production image
FROM base AS final

COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build
RUN php artisan storage:link || true

ENV PHP_OPCACHE_ENABLE=1

EXPOSE 8080
