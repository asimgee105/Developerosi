FROM php:8.2-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev

RUN docker-php-ext-install pdo_mysql bcmath zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Expose port 8000
EXPOSE 8000

# Install dependencies and start server
CMD ["sh", "-c", "composer install --no-interaction && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]
