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

# ---------------------------------------------------------------- dev

FROM base AS dev

RUN install-php-extensions xdebug

COPY infra/docker/api/php/dev.ini $PHP_INI_DIR/conf.d/zz-dev.ini

# Контейнер запускается под UID хоста: все записываемые пути в /tmp
ENV HOME=/tmp \
    COMPOSER_HOME=/tmp/composer \
    XDG_CONFIG_HOME=/tmp/config \
    XDG_DATA_HOME=/tmp/data \
    XDEBUG_MODE=off

# ---------------------------------------------------------------- vendor

FROM base AS vendor

ENV COMPOSER_ALLOW_SUPERUSER=1

# Отдельный слой под зависимости: меняется реже, чем код
COPY apps/api/composer.json apps/api/composer.lock ./

RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
      --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY apps/api ./

RUN composer dump-autoload --no-dev --classmap-authoritative \
 && php artisan package:discover --ansi

# ---------------------------------------------------------------- prod

FROM base AS prod

ENV APP_ENV=production \
    APP_DEBUG=false \
    OCTANE_SERVER=frankenphp \
    HOME=/tmp \
    XDG_CONFIG_HOME=/tmp/config \
    XDG_DATA_HOME=/tmp/data

COPY infra/docker/api/php/prod.ini $PHP_INI_DIR/conf.d/zz-prod.ini
COPY infra/docker/api/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

RUN useradd --uid 10001 --create-home --shell /usr/sbin/nologin app

COPY --from=vendor --chown=10001:10001 /app /app

# config:cache намеренно нет: он запёк бы env времени сборки
RUN php artisan route:cache && php artisan event:cache

USER 10001:10001

EXPOSE 8000

HEALTHCHECK --interval=10s --timeout=3s --start-period=20s --retries=3 \
  CMD php -r "exit(@file_get_contents('http://127.0.0.1:8000/health/live') === false ? 1 : 0);"

ENTRYPOINT ["entrypoint"]
CMD ["web"]
