FROM dunglas/frankenphp:latest-php8.3-alpine

# Install tool pendukung untuk install-php-extensions
RUN apk add --no-cache bash

# Install PHP extensions yang kamu minta
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
    xml

# COPY Composer dari image resmi composer ke dalam image FrankenPHP kita
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Berikan izin ke folder storage & bootstrap (penting untuk Laravel)
RUN mkdir -p storage bootstrap/cache