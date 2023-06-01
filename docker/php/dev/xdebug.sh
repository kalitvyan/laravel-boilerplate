#!/bin/bash -x

if [ "$ENV" == "dev" ] || [ "$ENV" == "test" ]; then
    pecl install xdebug
    docker-php-ext-enable xdebug
    mv /tmp/xdebug.ini /usr/local/etc/php/conf.d/
else
    rm /tmp/xdebug.ini
fi
