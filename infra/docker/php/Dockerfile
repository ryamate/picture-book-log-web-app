FROM php:8.4-fpm-alpine

# 必要な拡張
RUN apk add --no-cache \
    && docker-php-ext-install pdo_mysql bcmath

# Composer インストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
