FROM node:20-alpine AS nodebuild
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM composer:2 AS composerbuild
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader
COPY . .

FROM php:8.2-fpm-bullseye

# OS deps + PHP extensions
RUN apt-get update && apt-get install -y \
    nginx supervisor git unzip zip curl \
    libzip-dev libpng-dev libonig-dev libxml2-dev \
 && docker-php-ext-install pdo_mysql mbstring zip bcmath \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy app source
COPY . /var/www

# Copy vendor from composer stage
COPY --from=composerbuild /app/vendor /var/www/vendor

# Copy built assets from node stage (Laravel Vite default output biasanya public/build)
COPY --from=nodebuild /app/public/build /var/www/public/build

# Nginx + supervisor + entrypoint
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
