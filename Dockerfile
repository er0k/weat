FROM php:8.0.30-apache-bullseye

RUN apt-get update && apt-get install -y libicu-dev
RUN docker-php-ext-configure intl && docker-php-ext-install intl

RUN echo "ServerName weat" > /etc/apache2/conf-available/servername.conf && a2enconf servername
RUN ln -s /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
