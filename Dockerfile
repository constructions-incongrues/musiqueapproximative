# Composer
FROM composer:1 AS composer

# Any *-apache image listed on this page : https://store.docker.com/images/php
FROM php:5.6-fpm-alpine

# Copy composer binary
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Setup prestissimo
RUN composer global require hirak/prestissimo

# Define default working directory
WORKDIR /usr/local/src/app

# Install required packages and PHP extensions
RUN apk --no-cache --update add apache-ant bash make curl git zip \
    && docker-php-ext-install -j$(nproc) opcache pdo_mysql

# Copy application sources to container
COPY ./ /usr/local/src/app
COPY etc/docker/php.ini /usr/local/etc/php/

# Build application
RUN make install