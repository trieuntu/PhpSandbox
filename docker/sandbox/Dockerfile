FROM php:8.2-cli-alpine

# Install minimal system dependencies
RUN apk add --no-cache \
    oniguruma-dev \
    libzip-dev

# Install minimal PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring

# Copy restrictive sandbox php.ini
COPY php-sandbox.ini /usr/local/etc/php/conf.d/php-sandbox.ini

# Copy entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create non-root sandbox user
RUN addgroup -g 1001 sandbox \
    && adduser -u 1001 -G sandbox -s /bin/sh -D sandbox

# Create working directory and set ownership
RUN mkdir -p /var/www/sandbox && chown sandbox:sandbox /var/www/sandbox

# Sandbox temp execution directory will be a tmpfs mount from docker-compose
RUN mkdir -p /tmp/sandbox_exec && chown sandbox:sandbox /tmp/sandbox_exec

WORKDIR /var/www/sandbox

USER sandbox

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
