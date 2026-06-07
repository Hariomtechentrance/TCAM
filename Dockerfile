FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_sqlite \
    && pecl install sqlite3 2>/dev/null || true \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers deflate expires

# Allow .htaccess overrides site-wide
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Increase PHP upload limits for DB migration tool
RUN echo "upload_max_filesize = 32M\npost_max_size = 32M\nmemory_limit = 128M\nmax_execution_time = 120" \
    > /usr/local/etc/php/conf.d/uploads.ini

# Copy project files
COPY . /var/www/html/

# Create writable directories and set permissions
RUN mkdir -p /var/www/html/uploads /var/www/html/uploads/coaches \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads

# Persistent data directory (mount a Render Disk here)
RUN mkdir -p /var/data && chown www-data:www-data /var/data

# Startup script that links the persistent data dir into the web root
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
