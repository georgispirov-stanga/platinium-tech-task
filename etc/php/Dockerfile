FROM php:8.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
                              curl \
                              git \
                              unzip \
                              supervisor \
                              libonig-dev \
                              acl \
                              iputils-ping \
                              sudo \
                              npm \
                              gnupg2 \
                              libicu-dev \
                              libzip-dev \
                              zip

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    intl \
    zip \
    opcache

RUN pecl install redis && docker-php-ext-enable redis

RUN docker-php-ext-configure intl
RUN docker-php-ext-configure zip

COPY ./etc/php/conf.d /usr/local/etc/php/conf.d

COPY ../../config/preload.php /var/www/html/config/preload.php

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./etc/php/supervisord.conf /etc/supervisor

COPY ./etc/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
EXPOSE 9000