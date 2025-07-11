# Dockerfile

FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies
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
    libicu-dev

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash -
RUN apt-get install -y nodejs

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd
RUN docker-php-ext-install intl
# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# <<< MODIFICATION START: CREATE USER WITH DYNAMIC IDs >>>

# Add arguments for user/group IDs passed from docker-compose.yml
# It defaults to 1000 if not provided.
ARG UID=0
ARG GID=0

# Create the 'www' group and 'www' user with the dynamic IDs
RUN useradd -u 0 -o -g 0 -ms /bin/bash www

# <<< MODIFICATION END >>>

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions using the dynamic IDs
COPY --chown=${UID}:${GID} . /var/www

# Change current user to www
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]