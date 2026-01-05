# ---------- Stage 1: Node build (Vite) ----------
FROM node:20-alpine AS nodebuild

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build


# ---------- Stage 2: PHP deps (Composer) ----------
FROM composer:2 AS composerbuild

WORKDIR /app

# Copy composer files dulu agar cache optimal
COPY composer.json composer.lock ./
<<<<<<< HEAD
# IMPORTANT: no-scripts agar tidak memanggil artisan sebelum source dicopy
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader --no-scripts
# Baru copy source (termasuk artisan)
=======

# Install dependencies tanpa menjalankan script artisan
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader --no-scripts

# Copy source setelah vendor siap
>>>>>>> 0716ac3a38206321ee00cd636fd3f7bc63e604ab
COPY . .

# Optimalkan autoload (tanpa scripts)
RUN composer dump-autoload --optimize


# ---------- Stage 3: Runtime (Nginx + PHP-FPM) ----------
FROM php:8.2-fpm-bullseye

# Install system dependencies + PHP extensions
RUN apt-get update && apt-get install -y nginx supervisor git unzip zip curl libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy aplikasi
COPY . /var/www

# Copy vendor dari composer stage
COPY --from=composerbuild /app/vendor /var/www/vendor

# Copy hasil build Vite
COPY --from=nodebuild /app/public/build /var/www/public/build

# Nginx + Supervisor + Entrypoint
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
