FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git unzip curl libpng-dev oniguruma-dev \
    libxml2-dev libzip-dev zip

RUN docker-php-ext-install \
    pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
