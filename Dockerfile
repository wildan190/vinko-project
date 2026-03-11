FROM dunglas/frankenphp:php8.4-alpine

# Install tool pendukung
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

RUN mkdir -p storage bootstrap/cache