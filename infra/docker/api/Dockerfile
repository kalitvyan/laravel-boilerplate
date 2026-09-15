# syntax=docker/dockerfile:1

ARG FRANKENPHP_VERSION=1.12
ARG PHP_VERSION=8.5

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-trixie AS base

WORKDIR /app

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip \
 && rm -rf /var/lib/apt/lists/*

# OPcache в PHP 8.5 входит в ядро, отдельно не ставим
RUN install-php-extensions \
    pdo_pgsql \
    redis \
    intl \
    bcmath \
    pcntl \
    zip \
    @composer

    FROM base AS dev

    RUN install-php-extensions xdebug

    COPY php/dev.ini $PHP_INI_DIR/conf.d/zz-dev.ini

    # Контейнер запускается под UID хоста: все записываемые пути в /tmp
    ENV HOME=/tmp \
        COMPOSER_HOME=/tmp/composer \
        XDG_CONFIG_HOME=/tmp/config \
        XDG_DATA_HOME=/tmp/data \
        XDEBUG_MODE=off
