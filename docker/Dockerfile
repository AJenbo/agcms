FROM php:fpm

RUN apt-get update -y \
    && apt-get install -y locales libxml2-dev libpng-dev libjpeg62-turbo-dev libicu-dev \
    && echo 'da_DK.UTF-8 UTF-8\n' >> /etc/locale.gen \
    && echo 'da_DK UTF-8\n' >> /etc/locale.gen \
    && locale-gen da_DK.UTF-8 \
    && locale-gen da_DK \
    && docker-php-ext-configure gd --with-png-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install mysqli soap mbstring gd gettext intl \
    && docker-php-ext-enable mysqli soap mbstring gd gettext intl \
    && apt-get purge -y libxml2-dev libpng-dev libjpeg62-turbo-dev libicu-dev \
    && apt-get autoremove --purge -y \
    && apt-get clean -y
