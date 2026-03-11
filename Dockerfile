FROM dunglas/frankenphp:php8.4-alpine

# Install bash untuk kenyamanan terminal
RUN apk add --no-cache bash

# Install PHP extensions pesanan Laravel
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

# Ambil Composer terbaru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Set permission dasar
RUN mkdir -p storage bootstrap/cache