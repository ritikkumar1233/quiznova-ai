FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    zip \
    nodejs \
    npm

# Install PostgreSQL extension
RUN docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy all files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies
RUN npm install

# Build Vite assets
RUN npm run build

# Laravel optimizations
RUN php artisan config:cache || true
RUN php artisan route:cache || true
RUN php artisan view:cache || true

# Expose port
EXPOSE 10000

# Start app
CMD php artisan migrate --force && php artisan storage:link || true && php artisan optimize && php artisan serve --host=0.0.0.0 --port=10000