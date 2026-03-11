FROM dunglas/frankenphp:latest-php8.4-alpine

# Install system dependencies
RUN apk add --no-cache bash

# Install PHP extensions lengkap sesuai permintaan
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

# Copy Composer dari image resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Set permission dasar
RUN mkdir -p storage bootstrap/cache