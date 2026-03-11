FROM php:8.4-fpm-alpine

# Install system dependencies & PHP extensions
RUN apk add --no-cache bash libpng-dev libzip-dev icu-dev libpq-dev
RUN docker-php-ext-install bcmath gd intl zip pdo_pgsql

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Custom PHP Config untuk Upload 500MB+
RUN echo "upload_max_filesize=600M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=600M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=1G" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time=600" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app