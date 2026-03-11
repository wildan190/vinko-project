FROM dunglas/frankenphp:latest-php8.4-alpine

# Install tool pendukung
RUN apk add --no-cache bash

# Install PHP extensions (tetap sama, tapi untuk PHP 8.4)
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

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN mkdir -p storage bootstrap/cache