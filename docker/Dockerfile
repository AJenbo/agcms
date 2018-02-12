FROM php:fpm
MAINTAINER Anders Jenbo <anders@jenbo.dk>

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends locales libxml2-dev libpng16-16 libpng-dev libjpeg62-turbo libjpeg62-turbo-dev libicu-dev \
    && printf 'da_DK.UTF-8 UTF-8\n' >> /etc/locale.gen \
    && printf 'da_DK UTF-8\n' >> /etc/locale.gen \
    && locale-gen da_DK.UTF-8 \
    && locale-gen da_DK \
    && docker-php-ext-configure gd --with-png-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install pdo pdo_mysql soap mbstring gd gettext intl \
    && docker-php-ext-enable pdo pdo_mysql soap mbstring gd gettext intl \
    && docker-php-source delete \
    && apt-get purge -y libxml2-dev libpng-dev libjpeg62-turbo-dev libicu-dev \
    && apt-get autoremove --purge -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
