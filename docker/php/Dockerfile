FROM composer:2.7 AS composer

FROM php:8.3-fpm-alpine

ARG APP_USER=fordewind
ARG USER_ID=1000
ARG GROUP_ID=1000

RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev zip \
    && docker-php-ext-install intl mbstring pdo_mysql zip

RUN addgroup -g "${GROUP_ID}" "${APP_USER}" \
    && adduser -D -u "${USER_ID}" -G "${APP_USER}" "${APP_USER}" \
    && sed -i "s/^user = www-data/user = ${APP_USER}/; s/^group = www-data/group = ${APP_USER}/" /usr/local/etc/php-fpm.d/www.conf

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
