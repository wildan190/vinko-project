FROM dunglas/frankenphp:php8.4-alpine

# Install system dependencies
RUN apk add --no-cache bash

# Install PHP extensions
RUN install-php-extensions \
    bcmath \
    gd \
    intl \
    zip \
    opcache \
    pcntl \
    pdo_pgsql \
    redis \
    mbstring \
    xml \
    fileinfo

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Permission standar (Akan diperbaiki lagi lewat command line)
RUN mkdir -p storage bootstrap/cache