FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
        git unzip libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli zip gd \
    && a2enmod rewrite

# Composer diambil dari image resmi, tidak perlu install manual
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy manifest dulu supaya layer composer install ter-cache saat source code berubah
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-scripts --no-progress || true

COPY . .

# DocumentRoot diarahkan ke /public sesuai struktur project (public/ = entry point)
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
