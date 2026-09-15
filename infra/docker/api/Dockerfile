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
# xdebug, dev php.ini и entrypoint добавим на этапе 2
