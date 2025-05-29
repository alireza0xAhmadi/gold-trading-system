FROM php:8.2-cli as base

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libssl-dev \
    pkg-config \
    autoconf \
    g++ \
    make \
    openssl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Swoole
RUN printf "no\nno\nno\nno\nno\nno\nno\nno\nno\nno\nno\nno\nno\nno\n" | pecl install swoole \
    && docker-php-ext-enable swoole

# Install Redis
RUN printf "\n\n\n\n\n\n" | pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base as development

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader --no-dev=false

# Copy all source code
COPY . .

# Complete the composer setup
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create .env from example if not exists
RUN cp .env.example .env || echo ".env already exists"

# Generate app key (this will be overridden by environment variables)
RUN php artisan key:generate || echo "Key generation skipped"

EXPOSE 8000 9501

# Use a startup script instead of direct command
CMD ["sh", "-c", "composer install && php artisan key:generate --force && php artisan config:cache && php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000"]

FROM base as production

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8000

CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000", "--watcher=none"]
