# Base images
FROM composer:1 as composer
FROM php:5.6.40-cli-alpine

# Set working directory
WORKDIR /usr/local/src

# Set default timezone
RUN ln -sf /usr/share/zoneinfo/Europe/Paris /etc/localtime && \
    echo "date.timezone = Europe/Paris" > /usr/local/etc/php/php.ini

# Install Composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Installation et configuration de fixuid
# https://github.com/boxboat/fixuid
RUN addgroup --gid 1000 musiqueapproximative && \
    adduser --uid 1000 --ingroup musiqueapproximative --home /home/musiqueapproximative --shell /bin/sh --disabled-password --gecos "" musiqueapproximative && \
    curl -SsL https://github.com/boxboat/fixuid/releases/download/v0.4/fixuid-0.4-linux-amd64.tar.gz | tar -C /usr/local/bin -xzf - && \
    chown root:root /usr/local/bin/fixuid && \
    chmod 4755 /usr/local/bin/fixuid && \
    mkdir -p /etc/fixuid && \
    printf "user: musiqueapproximative\ngroup: musiqueapproximative\n" > /etc/fixuid/config.yml

# Install additional packages and PHP extensions
RUN apk --update --no-cache add bash curl gettext make zip && \
    docker-php-ext-install -j$(nproc) opcache pdo_mysql

# Copy application sources to container
COPY --chown=musiqueapproximative:musiqueapproximative ./src /usr/local/src

USER musiqueapproximative:musiqueapproximative
