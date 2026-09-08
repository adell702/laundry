# syntax=docker/dockerfile:1

FROM php:8.4-fpm-bookworm AS php-base

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfcgi-bin libfreetype6-dev libjpeg62-turbo-dev libonig-dev libpng-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath ctype gd mbstring opcache pcntl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/* \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i '/^user = /d; /^group = /d' /usr/local/etc/php-fpm.d/www.conf

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-laundry.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-laundry.conf

FROM php-base AS dependencies

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/root/.composer/cache \
    COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --no-progress

COPY . .

RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs storage/app/public storage/app/private \
    && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-dev --optimize --no-interaction \
    && composer check-platform-reqs --no-dev

FROM node:24-bookworm-slim AS assets

WORKDIR /app
COPY package.json package-lock.json .npmrc ./

RUN --mount=type=cache,target=/root/.npm npm ci --ignore-scripts

COPY resources ./resources
COPY --from=dependencies /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
COPY vite.config.js tailwind.config.js postcss.config.js ./
ARG VITE_APP_NAME="AA Laundry"
ENV VITE_APP_NAME=$VITE_APP_NAME
RUN npm run build

FROM php-base AS app

COPY --from=dependencies /var/www/html /var/www/html
COPY --from=assets /app/public/build ./public/build
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/laundry-entrypoint

RUN ln -s /var/www/html/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
ENTRYPOINT ["laundry-entrypoint"]
CMD ["php-fpm", "-F"]

FROM nginx:stable-alpine AS web

WORKDIR /var/www/html
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY public ./public
COPY --from=assets /app/public/build ./public/build
RUN ln -s /var/www/html/storage/app/public public/storage

EXPOSE 80
