FROM php:7.3-apache
COPY conf/php.ini /usr/local/etc/php/conf.d/

RUN apt-get update \
    && apt-get install -y libicu-dev libzip-dev zlib1g-dev unzip vim \
    && docker-php-ext-install mysqli intl zip

RUN a2enmod rewrite