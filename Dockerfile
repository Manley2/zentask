# syntax=docker/dockerfile:1

# ---------- Stage 1: Node build (Vite production build) ----------
FROM node:20-alpine AS nodebuild
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
RUN npm run build


# ---------- Stage 2: Composer deps (production) ----------
FROM composer:2 AS composerbuild
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --no-progress \
  --optimize-autoloader \
  --no-scripts

# Copy full source AFTER install (avoid scripts needing artisan before code exists)
COPY . .
RUN composer dump-autoload --optimize


# ---------- Stage 3: Runtime (Nginx + PHP-FPM + Supervisor) ----------
FROM php:8.2-fpm-bullseye

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      nginx supervisor \
      git unzip zip curl \
      libzip-dev libpng-dev libonig-dev libxml2-dev; \
    docker-php-ext-install pdo_mysql mbstring zip bcmath; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy app source
COPY . /var/www

# Copy vendor + built assets
COPY --from=composerbuild /app/vendor /var/www/vendor
COPY --from=nodebuild /app/public/build /var/www/public/build

# Nginx + Supervisor + Entrypoint
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

# IMPORTANT: sanitize entrypoint (anti CRLF + anti BOM) + executable
RUN set -eux; \
    sed -i 's/\r$//' /entrypoint.sh; \
    sed -i '1s/^\xEF\xBB\xBF//' /entrypoint.sh; \
    chmod 755 /entrypoint.sh; \
    /bin/sh -n /entrypoint.sh; \
    mkdir -p /var/www/storage /var/www/bootstrap/cache; \
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
