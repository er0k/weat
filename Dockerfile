FROM php:8.0.10-apache-bullseye

RUN apt-get update && apt-get install -y libicu-dev
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl
