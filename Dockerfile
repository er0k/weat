FROM php:8.0.30-apache-bullseye

RUN apt-get update && apt-get install -y libicu-dev
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl

RUN ln -s /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
