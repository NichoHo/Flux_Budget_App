FROM php:8.2-apache

# 1. Install necessary system dependencies for PostgreSQL, Zip, and Images
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo_pgsql pgsql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Enable Apache mod_rewrite for Laravel Routing
RUN a2enmod rewrite

# 3. Configure Apache DocumentRoot to point to Laravel's public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Install Node.js (Required for Vite asset building)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 6. Set working directory
WORKDIR /var/www/html

# 7. Copy your project files into the Docker container
COPY . .

# 8. Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# 9. Install Node dependencies and build the frontend Vite assets
RUN npm install && npm run build

# 10. Set the correct directory permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 11. Create a startup script to clear cache and run migrations on boot
RUN echo '#!/bin/bash\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
php artisan migrate --force\n\
exec apache2-foreground' > /usr/local/bin/start.sh \
&& chmod +x /usr/local/bin/start.sh

EXPOSE 80

# 12. Run the startup script when the container boots
CMD ["/usr/local/bin/start.sh"]