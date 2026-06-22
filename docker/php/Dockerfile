FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    autoconf \
    freetype-dev \
    g++ \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    make \
    oniguruma-dev \
    zip \
    unzip \
    git \
    curl

# Install PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        zip

# Install Redis extension via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy PHP configuration files
COPY php.ini     /usr/local/etc/php/conf.d/99-custom.ini
COPY php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
