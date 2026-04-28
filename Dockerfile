# syntax=docker/dockerfile:1
# ----------------------------------------------------------
# Stage 1 — Composer dependencies
# ----------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# ----------------------------------------------------------
# Stage 2 — Runtime image
# ----------------------------------------------------------
FROM php:8.2-apache

WORKDIR /var/www/html

# System deps + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libonig-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pgsql pdo_pgsql gd mbstring zip \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

# PHP config
RUN { \
    echo 'upload_max_filesize=10M'; \
    echo 'post_max_size=12M'; \
    echo 'memory_limit=256M'; \
    echo 'max_file_uploads=20'; \
    echo 'max_execution_time=120'; \
    echo 'max_input_time=120'; \
} > /usr/local/etc/php/conf.d/custom.ini

# Apache: servir de public/ como DocumentRoot
RUN sed -ri 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's|<Directory /var/www/>|<Directory /var/www/html/public/>|g' \
        /etc/apache2/apache2.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' \
        /etc/apache2/apache2.conf

# Apache: porta 8080 (Railway não abre 80)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' \
        /etc/apache2/sites-available/000-default.conf

# Copiar código da aplicação
COPY . /var/www/html

# Copiar vendor gerado no stage 1
COPY --from=vendor /app/vendor /var/www/html/vendor

# Permissões de storage
RUN mkdir -p /var/www/html/storage/{logs,cache/di,uploads} \
             /var/www/html/public/uploads \
    && chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/public/uploads

# Script de startup
COPY docker/apache-start.sh /usr/local/bin/apache-start.sh
RUN chmod +x /usr/local/bin/apache-start.sh

EXPOSE 8080
CMD ["/usr/local/bin/apache-start.sh"]
