# Kokango API container (used by Render; works on any Docker host).
# PHP 8.3 + Apache serving public/. Settings come from environment variables (see deploy/render/README.md).
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libzip-dev libicu-dev unzip git \
 && docker-php-ext-install pdo_mysql zip intl bcmath opcache \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

# Apache serves Laravel's public/ folder and listens on the port the platform gives ($PORT, set at start-up)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
 && echo 'ServerName localhost' >> /etc/apache2/apache2.conf \
 && { echo 'opcache.enable=1'; echo 'opcache.validate_timestamps=0'; echo 'opcache.memory_consumption=64'; } > /usr/local/etc/php/conf.d/opcache.ini \
 && { echo 'upload_max_filesize=6M'; echo 'post_max_size=8M'; echo 'memory_limit=256M'; } > /usr/local/etc/php/conf.d/kokango.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# PHP libraries first (cached between builds while composer.lock is unchanged)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
 && php artisan package:discover --ansi \
 && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod +x docker/start.sh

CMD ["docker/start.sh"]
