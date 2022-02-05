FROM phpdockerio/php:8.1-fpm
WORKDIR "/copy2cloud"

RUN apt-get update; \
    apt-get -y --no-install-recommends install \
        git \
        curl \
        php8.1-intl \
        php8.1-bcmath \
        php8.1-redis \
        php8.1-xdebug; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/bin --filename=composer && chmod +x /usr/bin/composer
