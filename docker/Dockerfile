FROM php:fpm-alpine
RUN apk add --update --no-cache libxml2-dev gettext-dev gettext icu-dev icu \
    && docker-php-ext-install mysqli soap mbstring gettext intl \
    && docker-php-ext-enable mysqli soap mbstring gettext intl \
    && apk del libxml2-dev gettext-dev icu-dev
