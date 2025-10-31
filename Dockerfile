# ISP Status Page - Dockerfile
# Multi-stage build for production-ready image

FROM php:8.2-apache as base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_sqlite \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    zip

# Enable Apache modules
RUN a2enmod rewrite headers

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY src/ /var/www/html/

# Copy database file (will be created if doesn't exist)
RUN touch /var/www/html/database.db

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/tmp \
    && chmod -R 777 /var/www/html/logs \
    && chmod 666 /var/www/html/database.db

# Configure Apache
RUN sed -i 's!/var/www/html!/var/www/html/webroot!g' /etc/apache2/sites-available/000-default.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure cron for monitoring checks
COPY docker/crontab /etc/cron.d/isp-status-cron
RUN chmod 0644 /etc/cron.d/isp-status-cron \
    && crontab /etc/cron.d/isp-status-cron \
    && touch /var/log/cron.log

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s \
    CMD curl -f http://localhost/ || exit 1

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Start Apache and cron
CMD ["apache2-foreground"]

# Development stage
FROM base as development

# Install development dependencies
RUN apt-get update && apt-get install -y \
    vim \
    nano \
    && rm -rf /var/lib/apt/lists/*

# Enable PHP errors for development
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/errors.ini

# Install composer dependencies (dev)
RUN composer install --prefer-dist --no-interaction

# Production stage
FROM base as production

# Disable PHP errors for production
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/errors.ini

# Install composer dependencies (no dev)
RUN composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader

# Remove composer
RUN rm /usr/bin/composer
