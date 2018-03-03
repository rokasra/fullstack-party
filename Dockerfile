FROM php:7.2

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Zip
RUN apt-get update && apt-get install -y libssl-dev zlib1g-dev && docker-php-ext-install zip

# xdebug
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/xdebug.ini

COPY . /var/www/html
WORKDIR /var/www/html/

CMD php -S 0.0.0.0:80 -t /var/www/html/public
