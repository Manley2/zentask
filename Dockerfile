FROM php:8.2-fpm

# System deps
RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# (Opsional) Kalau nanti mau build dependency di image, bisa diaktifkan.
# Untuk sekarang kita pakai volume mount, jadi biar cepat & minim error.
