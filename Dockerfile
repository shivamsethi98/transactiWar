FROM php:8.2-apache

# Install PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        gd \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy custom PHP config
COPY config/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy Apache config
COPY config/apache.conf /etc/apache2/sites-available/000-default.conf

# Create upload directory (outside webroot)
RUN mkdir -p /uploads/avatars && chown www-data:www-data /uploads/avatars

# Create log directory
RUN mkdir -p /var/log/php && chown www-data:www-data /var/log/php

# Copy application files
COPY app/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy setup scripts and SQL
COPY sql/ /sql/
COPY setup.sh /setup.sh
COPY create_accounts.sh /create_accounts.sh
RUN chmod +x /setup.sh /create_accounts.sh

EXPOSE 80

CMD ["/setup.sh"]
