FROM php:fpm-alpine
RUN apk add --update --no-cache libxml2-dev libpng-dev libpng libjpeg-turbo-dev libjpeg-turbo gettext-dev gettext icu-dev icu \
    && docker-php-ext-configure gd --with-png-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install mysqli soap mbstring gd gettext intl \
    && docker-php-ext-enable mysqli soap mbstring gd gettext intl \
    && apk del libxml2-dev libpng-dev libjpeg-turbo-dev gettext-dev icu-dev
