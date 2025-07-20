# Dockerfile

FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    nano \
    curl \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-install intl

# Install Redis extension for better caching
RUN pecl install redis && docker-php-ext-enable redis

# Install OPcache for performance
RUN docker-php-ext-install opcache

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Add arguments for user/group IDs passed from docker-compose.yml
ARG UID=1000
ARG GID=1000

RUN groupadd -g ${GID} www \
    && useradd -u ${UID} -g www -ms /bin/bash www

# Copy composer files first for better layer caching
COPY composer.json composer.lock* ./

# Copy the rest of the application code
COPY . /var/www

# Now run composer install (artisan will be present)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy existing application directory permissions using the dynamic IDs
COPY --chown=${UID}:${GID} . /var/www

# Set proper permissions
RUN chown -R www:www /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Copy optimized PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]