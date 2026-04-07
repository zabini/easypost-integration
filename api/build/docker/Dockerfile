FROM php:8.4-fpm

ENV HOME=/var/www
ENV USER=www-data
ARG UID=33

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libfcgi-bin zip unzip tzdata default-mysql-client \
    && docker-php-ext-install zip pdo_mysql mbstring exif pcntl bcmath gd sockets opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY build/php/ /tmp/build-php/
COPY build/docker/entrypoint.sh /usr/local/bin/api-entrypoint

RUN if ls /tmp/build-php/*.ini >/dev/null 2>&1; then cp /tmp/build-php/*.ini /usr/local/etc/php/conf.d/; fi \
    && if ls /tmp/build-php/*.conf >/dev/null 2>&1; then cp /tmp/build-php/*.conf /usr/local/etc/php-fpm.d/; fi \
    && rm -rf /tmp/build-php \
    && chmod +x /usr/local/bin/api-entrypoint

RUN usermod -u $UID $USER

# Prepare php fpm healthcheck
RUN curl -o /usr/local/bin/php-fpm-healthcheck \
    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Get 2 Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN chown -R $USER:$USER $HOME

WORKDIR $HOME

USER $USER

COPY database/ database/

COPY --chown=$USER:$USER composer.* ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs \
    --no-interaction \
    # --no-plugins \
    --prefer-dist

COPY --chown=$USER:$USER . .

RUN composer dump-autoload --optimize && \
    php artisan event:cache && \
    php artisan route:cache && \
    php artisan view:cache

ENTRYPOINT ["api-entrypoint"]
CMD ["php-fpm"]
