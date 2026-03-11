FROM php:8.4-fpm-alpine

# Install system dependencies + Library untuk gambar
RUN apk add --no-cache \
    bash \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    libpq-dev \
    autoconf \
    gcc \
    g++ \
    make \
    nodejs \
    npm

# Konfigurasi GD agar mendukung JPEG, FreeType, dan WebP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install bcmath gd intl zip pdo_pgsql pcntl

# Install Redis
RUN pecl install redis && docker-php-ext-enable redis

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Custom PHP Config untuk Upload 500MB+
RUN echo "upload_max_filesize=600M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=600M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=1G" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time=600" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app