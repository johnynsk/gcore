FROM php:5.6-fpm
RUN apt-get update
RUN apt-get install -y \
    libssl-dev \
    libmemcached-dev

RUN pecl install mongo \
    && docker-php-ext-enable mongo

RUN pecl install memcached \
    && docker-php-ext-enable memcached
