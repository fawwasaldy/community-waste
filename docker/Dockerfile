FROM ubuntu:24.04

LABEL maintainer="Laravel"

ARG DEBIAN_FRONTEND=noninteractive

# Install PHP 8.5 via ondrej PPA and required extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        software-properties-common \
        curl \
        ca-certificates \
        unzip \
    && add-apt-repository ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.5-cli \
        php8.5-dev \
        php-pear \
        make \
        php8.5-mbstring \
        php8.5-xml \
        php8.5-zip \
        php8.5-bcmath \
        php8.5-curl \
        supervisor \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install mongodb \
    && echo "extension=mongodb.so" > /etc/php/8.5/mods-available/mongodb.ini \
    && phpenmod -v 8.5 mongodb

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php8.5 -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
