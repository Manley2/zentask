# syntax=docker/dockerfile:1

# ---------- Stage 1: Composer deps ----------
FROM composer:2 AS composerbuild
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader --no-scripts


# ---------- Stage 2: Runtime ----------
FROM php:8.2-fpm-bullseye

# System deps + PHP extensions + Supervisor + Nginx + Node.js 20 + dos2unix (fix CRLF/BOM)
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      ca-certificates curl gnupg \
      nginx supervisor git unzip zip dos2unix \
      libzip-dev libpng-dev libonig-dev libxml2-dev; \
    docker-php-ext-install pdo_mysql mbstring zip bcmath; \
    \
    mkdir -p /etc/apt/keyrings; \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg; \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" > /etc/apt/sources.list.d/nodesource.list; \
    apt-get update; \
    apt-get install -y --no-install-recommends nodejs; \
    \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Cache-friendly node install
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# Copy app source
COPY . /var/www

# Copy vendor from composer stage
COPY --from=composerbuild /app/vendor /var/www/vendor

# Nginx + Supervisor + Entrypoint
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

# Fix CRLF/BOM + permissions
RUN dos2unix /entrypoint.sh \
    && chmod +x /entrypoint.sh \
    && mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

# Container ports: nginx=80, vite=5173
EXPOSE 80 5173

# Extra-safe: run through sh
ENTRYPOINT ["sh", "/entrypoint.sh"]
