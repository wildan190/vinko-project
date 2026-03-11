FROM dunglas/frankenphp:latest-php8.3-alpine

# Install system dependencies & PHP extensions
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

# Set working directory
WORKDIR /app