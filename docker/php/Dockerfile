FROM php:8.2.6-fpm

ARG COMPOSER_VERSION=2.5.7
ARG NODE_VERSION=18.0.0

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    build-essential \
    gosu \
    locales \
    mc \
    nano \
    unzip \
    zip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions

# Graphics Draw
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Multibyte String
RUN apt-get update && apt-get install -y libonig-dev && docker-php-ext-install mbstring

# Miscellaneous
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install exif
RUN docker-php-ext-install pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer -version=$COMPOSER_VERSION

# Install Cron
#RUN apt-get update && apt-get install -y cron
#RUN echo "* * * * * root php /var/www/artisan schedule:run >> /var/log/cron.log 2>&1" >> /etc/crontab
#RUN touch /var/log/cron.log

EXPOSE 9000

CMD ["php-fpm"]

#CMD bash -c "cron && php-fpm"
