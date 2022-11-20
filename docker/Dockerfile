FROM php:8.1-fpm

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends locales libonig-dev libxml2-dev libpng16-16 libpng-dev libjpeg62-turbo libjpeg62-turbo-dev libicu-dev libzip4 libzip-dev \
    && printf 'da_DK.UTF-8 UTF-8\n' >> /etc/locale.gen \
    && printf 'da_DK UTF-8\n' >> /etc/locale.gen \
    && locale-gen da_DK.UTF-8 \
    && locale-gen da_DK \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql soap mbstring gd gettext intl zip \
    && docker-php-ext-enable pdo pdo_mysql soap mbstring gd gettext intl zip \
    && docker-php-source delete \
    && apt-get purge -y libonig-dev libxml2-dev libpng-dev libjpeg62-turbo-dev libicu-dev libzip-dev \
    && apt-get autoremove --purge -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
