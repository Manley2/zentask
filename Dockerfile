# ---------- Stage 1: Node build (Vite) ----------baru
FROM node:20-alpine AS nodebuild
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build
# ---------- Stage 2: PHP deps (Composer) ----------
FROM composer:2 AS composerbuild
WORKDIR /app
# Copy composer files dulu (biar cache kepakai)
COPY composer.json composer.lock ./
# IMPORTANT: no-scripts agar tidak memanggil artisan sebelum source dicopy
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader --no-scripts
# Baru copy source (termasuk artisan)
COPY . .
# (Optional) rapikan autoload (tanpa menjalankan scripts)
RUN composer dump-autoload --optimize
# ---------- Stage 3: Runtime (Nginx + PHP-FPM) ----------
FROM php:8.2-fpm-bullseye
RUN apt-get update && apt-get install -y \
    nginx supervisor git unzip zip curl \
    libzip-dev libpng-dev libonig-dev libxml2-dev \
 && docker-php-ext-install pdo_mysql mbstring zip bcmath \
 && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www
COPY . /var/www
# Copy vendor dari composer stage
COPY --from=composerbuild /app/vendor /var/www/vendor
# Copy assets hasil build vite
COPY --from=nodebuild /app/public/build /var/www/public/build
# Nginx + supervisor + entrypoint
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
# Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true
EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
