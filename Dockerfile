# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

FROM php:8.2-apache

WORKDIR /var/www/html

# Dependencias do sistema e extensoes PHP necessarias para o projeto
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
    && test "$(apache2ctl -M 2>/dev/null | awk '/mpm_(event|worker|prefork)_module/ {count++} END {print count+0}')" -eq 1 \
    && rm -rf /var/lib/apt/lists/*

# Permite uso de .htaccess na pasta publica do Apache
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Limites de upload e runtime PHP
RUN { \
    echo 'upload_max_filesize=10M'; \
    echo 'post_max_size=12M'; \
    echo 'memory_limit=256M'; \
    echo 'max_file_uploads=20'; \
    echo 'max_execution_time=120'; \
    echo 'max_input_time=120'; \
} > /usr/local/etc/php/conf.d/uploads.ini

# Copia codigo da aplicacao
COPY . /var/www/html

# Copia dependencias do Composer geradas na etapa anterior
COPY --from=vendor /app/vendor /var/www/html/vendor

# Permissoes para escrita em storage
RUN mkdir -p /var/www/html/storage/logs /var/www/html/storage/cache /var/www/html/storage/uploads \
    && chown -R www-data:www-data /var/www/html/storage

# Runtime startup script to normalize Apache MPM state before launch.
COPY docker/apache-start.sh /usr/local/bin/apache-start.sh
RUN chmod +x /usr/local/bin/apache-start.sh

EXPOSE 8080
CMD ["/usr/local/bin/apache-start.sh"]
