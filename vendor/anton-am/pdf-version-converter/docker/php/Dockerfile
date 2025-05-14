FROM composer:latest as composer
FROM php:8.1-fpm

ADD ./docker/php/app-config.ini /usr/local/etc/php/conf.d/

ENV LANG=en_US.UTF-8 \
    LANGUAGE=en_US.UTF-8 \
    LC_ALL=en_US.UTF-8

# Update
RUN apt update -y && \
    apt install -y \
        apt-utils \
        git \
        libyaml-dev \
        libzip-dev \
        locales \
        vim \
        wget \
        ghostscript \
        zip

# PECL Packages
RUN pecl install xdebug

# Locale
RUN sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
    sed -i -e 's/# ru_RU.UTF-8 UTF-8/ru_RU.UTF-8 UTF-8/' /etc/locale.gen && \
    dpkg-reconfigure --frontend=noninteractive locales && \
    update-locale LANG=en_US.UTF-8

# Composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Xdebug
RUN apt-get install -y iproute2
RUN echo "zend_extension=xdebug.so" > /usr/local/etc/php/conf.d/xdebug.ini \
    && /sbin/ip route | awk '/default/ { printf "xdebug.client_host="$3 }' >> /usr/local/etc/php/conf.d/xdebug.ini

CMD ["php-fpm"]