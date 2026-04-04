FROM composer:2 AS composer_deps

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM node:20-alpine AS frontend_build

WORKDIR /app/frontend

COPY frontend/package.json frontend/package-lock.json ./

RUN npm ci

COPY frontend/ ./

RUN npm run build

FROM php:8.3-apache

ENV APP_ENV=prod \
    APP_DEBUG=0

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        apache2-utils \
        libicu-dev \
        libzip-dev \
        libpq-dev \
        libonig-dev \
    && docker-php-ext-install \
        intl \
        mysqli \
        opcache \
        pdo \
        pdo_mysql \
        zip \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/servername.conf /etc/apache2/conf-available/servername.conf
COPY docker/apache/basic-auth.conf /etc/apache2/conf-available/basic-auth.conf
COPY docker/auth-entrypoint.sh /usr/local/bin/auth-entrypoint.sh

RUN a2enconf servername \
    && chmod +x /usr/local/bin/auth-entrypoint.sh

WORKDIR /var/www/html

COPY . .
COPY --from=composer_deps /app/vendor ./vendor
COPY --from=frontend_build /app/frontend/dist/ ./public/

RUN composer dump-autoload \
        --no-dev \
        --classmap-authoritative \
        --no-interaction \
    && rm -rf frontend/node_modules frontend/dist var/cache/* var/log/* \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/auth-entrypoint.sh"]
CMD ["apache2-foreground"]
