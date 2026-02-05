# Multi-stage build для PHP 8.4
FROM mlocati/php-extension-installer:2.4 AS php-extension-installer
FROM composer:2.8 AS composer

FROM php:8.4-cli-bookworm AS dev-php

ARG UID=1000
ARG GID=1000

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    bcmath \
    intl \
    pcntl \
    opcache \
    yaml \
    zip \
    curl \
    mbstring \
    xml \
    dom \
    fileinfo

COPY conf.d/php.ini /usr/local/etc/php/conf.d/php.ini

ENV COMPOSER_CACHE_DIR=/tmp/composer/cache

WORKDIR /var/www/html

RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data
RUN mkdir -p /tmp/composer/cache && chown -R www-data:www-data /tmp/composer/cache

USER www-data

CMD ["tail", "-f", "/dev/null"]
