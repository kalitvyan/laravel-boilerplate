FROM php:8.2.6-fpm

# Set main params.
ARG BUILD_ENV=dev
ENV ENV=$BUILD_ENV
ENV APP_HOME=/var/www/html
ARG HOST_UID=1000
ARG HOST_GID=1000
ENV USERNAME=www-data
ARG IS_DOCKER_CONTAINER=1
ENV IS_DOCKER_CONTAINER=$IS_DOCKER_CONTAINER

ARG NODE_VERSION=18

# Install packages and dependencies.
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
    build-essential \
    ca-certificates \
    locales \
    gnupg \
    gosu \
    procps \
    curl \
    nano \
    mc \
    git \
    zip \
    unzip \
    supervisor \
    cron \
    sudo \
    sqlite3 \
    dnsutils \
    libicu-dev \
    zlib1g-dev \
    libxml2 \
    libxml2-dev \
    libreadline-dev \
    libzip-dev \
    libcap2-bin \
    libpng-dev \
    librsvg2-bin \
    libfreetype6-dev \
    libjpeg-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libmemcached-dev \
    libcurl4-openssl-dev \
    libldap2-dev \
    libgmp-dev \
    libmcrypt-dev \
    libpq-dev \
    libpcre3-dev

# Install Node.js, npm and yarn.
RUN curl -sLS https://deb.nodesource.com/setup_$NODE_VERSION.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm && \
    npm install -g yarn

# Clear cache and cleanup.
RUN apt-get clean && \
    apt-get -y autoremove && \
    rm -rf /tmp/* && \
    rm -rf /var/tmp/* && \
    rm -rf /var/list/apt/* && \
    rm -rf /var/lib/apt/lists/*

# Install PHP extensions.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd \
        intl \
        exif \
        bcmath \
        curl \
        gmp \
        mbstring \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        mysqli \
        ldap \
        opcache \
        sockets \
        zip

# TODO: deal something with PHP extensions. Installing it from ome place, may be apt?

# Install PHP extensions which are didn't supported in official image.
# COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
# RUN install-php-extensions http

# Install PECL extensions.
RUN set -ex && \
    pecl install redis && \
    docker-php-ext-enable redis

# Create document root, fix permissions for www-data user and change owner to www-data.
RUN mkdir -p $APP_HOME/public && \
    mkdir -p /home/$USERNAME && chown $USERNAME:$USERNAME /home/$USERNAME && \
    usermod -o -u $HOST_UID $USERNAME -d /home/$USERNAME && \
    groupmod -o -g $HOST_GID $USERNAME && \
    chown -R ${USERNAME}:${USERNAME} $APP_HOME

# Put php config for Laravel.
COPY ./docker/php/$BUILD_ENV/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/php/$BUILD_ENV/php.ini /usr/local/etc/php/php.ini
COPY ./docker/php/$BUILD_ENV/opcache.ini /usr/local/etc/php/opcache.ini

# Install Xdebug in case dev/test environment.
COPY ./docker/php/dev/xdebug.sh /tmp/
COPY ./docker/php/dev/xdebug.ini /tmp/xdebug.ini
RUN chmod u+x /tmp/xdebug.sh && /tmp/xdebug.sh

# Install Composer.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1

# Install Composer with specified version.
# ARG COMPOSER_VERSION=2.5.7
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer -version=$COMPOSER_VERSION

# Add supervisor.
RUN mkdir -p /var/log/supervisor
COPY --chown=root:root ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chown=root:crontab ./docker/cron /var/spool/cron/crontabs/root
RUN chmod 0600 /var/spool/cron/crontabs/root

# Set working directory.
WORKDIR $APP_HOME

USER ${USERNAME}

# Copy source files and config file
COPY --chown=${USERNAME}:${USERNAME} . $APP_HOME/
COPY --chown=${USERNAME}:${USERNAME} .env.$ENV $APP_HOME/.env

# Install all PHP dependencies
RUN if [ "$BUILD_ENV" == "dev" ] || [ "$BUILD_ENV" == "test" ]; then COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader --no-interaction --no-progress; \
    else COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader --no-interaction --no-progress --no-dev; \
    fi

USER root
