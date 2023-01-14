FROM php:5.6-apache 

RUN a2enmod rewrite

RUN docker-php-ext-install mysqli pdo_mysql
RUN apt-get update && apt-get install -y wkhtmltopdf xvfb
RUN pecl install redis-2.2.8 && echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN sed -i 's/mozilla\/DST_Root_CA_X3.crt/!mozilla\/DST_Root_CA_X3.crt/g' /etc/ca-certificates.conf
RUN update-ca-certificates

RUN mkdir /opt/oracle 

ADD instantclient-basic-linux.x64-12.1.0.2.0.zip /opt/oracle
ADD instantclient-sdk-linux.x64-12.1.0.2.0.zip /opt/oracle

RUN apt-get install unzip

# Install Oracle Instantclient
RUN cd /opt/oracle \
    && unzip /opt/oracle/instantclient-basic-linux.x64-12.1.0.2.0.zip -d /opt/oracle \
    && unzip /opt/oracle/instantclient-sdk-linux.x64-12.1.0.2.0.zip -d /opt/oracle \
    && ln -s /opt/oracle/instantclient_12_1/libclntsh.so.12.1 /opt/oracle/instantclient_12_1/libclntsh.so \
    && ln -s /opt/oracle/instantclient_12_1/libclntshcore.so.12.1 /opt/oracle/instantclient_12_1/libclntshcore.so \
    && ln -s /opt/oracle/instantclient_12_1/libocci.so.12.1 /opt/oracle/instantclient_12_1/libocci.so \
    && rm -rf /opt/oracle/*.zip
    
# Install Oracle extensions
RUN docker-php-ext-configure pdo_oci --with-pdo-oci=instantclient,/opt/oracle/instantclient_12_1,12.1 \
       && echo 'instantclient,/opt/oracle/instantclient_12_1/' | pecl install oci8-2.0.12 \
       && docker-php-ext-enable \
               oci8

RUN echo /opt/oracle/instantclient_12_1/ > /etc/ld.so.conf.d/oracle-insantclient.conf \
    && ldconfig \
    && apt-get install libaio1

