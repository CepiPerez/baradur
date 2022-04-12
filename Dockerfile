FROM php:5.6-apache 

RUN a2enmod rewrite

RUN docker-php-ext-install mysqli
RUN apt-get update && apt-get install -y wkhtmltopdf xvfb
RUN pecl install redis-2.2.8 && echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

