FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/sites-available/default-ssl.conf \
    /etc/apache2/apache2.conf

COPY docker/apache/app.conf /etc/apache2/conf-available/app.conf
RUN a2enconf app

WORKDIR /var/www/html

COPY . /var/www/html

RUN mkdir -p storage/logs storage/backups public/uploads \
    && chown -R www-data:www-data storage public/uploads
